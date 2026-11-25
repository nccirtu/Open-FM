<?php

namespace App\Models;

use App\Enums\ChangeOrderStatus;
use App\Enums\CostEntryKind;
use App\Enums\ProjectHealth;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Enums\TaskStatus;
use App\Flow\Concerns\HasWorkflows;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\HasManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Slimani\MediaManager\Concerns\InteractsWithMediaFiles;

class Project extends Model
{
    use BelongsToOrganization;
    use HasFactory;
    use HasManager;
    use HasUuids;
    use HasWorkflows;
    use InteractsWithMediaFiles;

    protected $fillable = [
        'property_id', 'manager_id', 'code', 'name', 'description', 'type', 'status', 'health',
        'start_date', 'end_date', 'budget', 'created_by',
    ];

    protected $attributes = [
        'status' => 'open',
        'health' => 'green',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ProjectType::class,
            'status' => ProjectStatus::class,
            'health' => ProjectHealth::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'budget' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Project $project): void {
            $project->created_by ??= auth()->id();
        });
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function wasteCollections(): HasMany
    {
        return $this->hasMany(WasteCollection::class);
    }

    public function phases(): HasMany
    {
        return $this->hasMany(ProjectPhase::class)->orderBy('sort');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class)->orderBy('due_date');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function budgetPositions(): HasMany
    {
        return $this->hasMany(BudgetPosition::class)->orderBy('sort');
    }

    public function costEntries(): HasMany
    {
        return $this->hasMany(CostEntry::class);
    }

    public function changeOrders(): HasMany
    {
        return $this->hasMany(ChangeOrder::class);
    }

    public function plannedBudget(): float
    {
        $positions = (float) $this->budgetPositions()->sum('budget');
        $base = $positions > 0 ? $positions : (float) ($this->budget ?? 0);

        return $base + (float) $this->changeOrders()->where('status', ChangeOrderStatus::Approved->value)->sum('amount');
    }

    public function actualCosts(): float
    {
        return (float) $this->costEntries()->where('kind', CostEntryKind::Actual->value)->sum('amount');
    }

    public function estimatedCosts(): float
    {
        return (float) $this->costEntries()->where('kind', CostEntryKind::Estimate->value)->sum('amount');
    }

    public function budgetVariance(): float
    {
        return $this->plannedBudget() - $this->actualCosts();
    }

    public function budgetUsage(): ?int
    {
        $planned = $this->plannedBudget();

        return $planned > 0 ? (int) round($this->actualCosts() / $planned * 100) : null;
    }

    public function files(): MorphToMany
    {
        return $this->mediaFiles('files');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', array_map(fn (ProjectStatus $status) => $status->value, ProjectStatus::openCases()));
    }

    public function progress(): int
    {
        $total = $this->tasks()->count();

        if ($total === 0) {
            return 0;
        }

        return (int) round($this->tasks()->where('status', TaskStatus::Done->value)->count() / $total * 100);
    }

    public function nextMilestone(): ?Milestone
    {
        return $this->milestones()->whereNull('completed_at')->whereNotNull('due_date')->orderBy('due_date')->first();
    }

    /**
     * @return array<int, string>
     */
    public function indicators(): array
    {
        $indicators = [];
        $today = now()->startOfDay();

        if ($this->status->isOpen() && $this->end_date !== null && $this->end_date->lt($today)) {
            $indicators[] = 'end_date_passed';
        }

        if ($this->milestones()->whereNull('completed_at')->whereDate('due_date', '<', $today)->exists()) {
            $indicators[] = 'milestones_overdue';
        }

        if ($this->tasks()->where('status', '!=', TaskStatus::Done->value)->whereDate('due_date', '<', $today)->exists()) {
            $indicators[] = 'tasks_overdue';
        }

        if ($this->plannedBudget() > 0 && $this->budgetVariance() < 0) {
            $indicators[] = 'budget_exceeded';
        } elseif (($usage = $this->budgetUsage()) !== null && $usage >= 90) {
            $indicators[] = 'budget_nearly_used';
        }

        return $indicators;
    }

    public function effectiveHealth(): ProjectHealth
    {
        $indicators = $this->indicators();

        if (array_intersect(['end_date_passed', 'milestones_overdue', 'budget_exceeded'], $indicators) !== []) {
            return ProjectHealth::Red;
        }

        if ($indicators !== [] && ($this->health ?? ProjectHealth::Green) === ProjectHealth::Green) {
            return ProjectHealth::Yellow;
        }

        return $this->health ?? ProjectHealth::Green;
    }
}
