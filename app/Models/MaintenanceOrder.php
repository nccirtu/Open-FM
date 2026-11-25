<?php

namespace App\Models;

use App\Enums\MaintenanceOrderStatus;
use App\Flow\Concerns\HasWorkflows;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\MaintenanceOrderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Slimani\MediaManager\Concerns\InteractsWithMediaFiles;

class MaintenanceOrder extends Model
{
    /** @use HasFactory<MaintenanceOrderFactory> */
    use BelongsToOrganization, HasFactory;

    use HasUuids;
    use HasWorkflows;
    use InteractsWithMediaFiles;

    protected $fillable = [
        'maintenance_plan_id',
        'asset_id',
        'due_at',
        'status',
        'notes',
        'tasks',
    ];

    protected $attributes = [
        'status' => 'open',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_at' => 'date',
            'status' => MaintenanceOrderStatus::class,
            'completed_at' => 'datetime',
            'protocol_files' => 'array',
            'tasks' => 'array',
            'completed_tasks' => 'array',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(MaintenancePlan::class, 'maintenance_plan_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function costEntries(): MorphMany
    {
        return $this->morphMany(CostEntry::class, 'costable');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', MaintenanceOrderStatus::Open);
    }

    public static function firstOrCreateForPlanDueDate(MaintenancePlan $plan): self
    {
        $existing = self::query()
            ->where('maintenance_plan_id', $plan->getKey())
            ->whereDate('due_at', $plan->next_due_at->toDateString())
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $order = new self([
            'maintenance_plan_id' => $plan->getKey(),
            'asset_id' => $plan->asset_id,
            'due_at' => $plan->next_due_at->toDateString(),
            'tasks' => $plan->tasks,
        ]);

        $order->organization_id = $plan->organization_id;
        $order->save();

        return $order;
    }

    public function dueColor(): string
    {
        if ($this->status !== MaintenanceOrderStatus::Open) {
            return 'gray';
        }

        if ($this->due_at->isPast() && ! $this->due_at->isToday()) {
            return 'danger';
        }

        return $this->due_at->lessThanOrEqualTo(now()->addDays(14)) ? 'warning' : 'gray';
    }
}
