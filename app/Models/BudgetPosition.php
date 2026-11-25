<?php

namespace App\Models;

use App\Enums\ChangeOrderStatus;
use App\Enums\CostEntryKind;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetPosition extends Model
{
    use HasUuids;

    protected $fillable = ['project_id', 'name', 'cost_group', 'cost_center_id', 'budget', 'sort'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['budget' => 'decimal:2'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(ClassificationNode::class, 'cost_center_id');
    }

    public function costGroupLabel(): ?string
    {
        return $this->costCenter?->label() ?? $this->cost_group;
    }

    public function costEntries(): HasMany
    {
        return $this->hasMany(CostEntry::class);
    }

    public function changeOrders(): HasMany
    {
        return $this->hasMany(ChangeOrder::class);
    }

    public function approvedChanges(): float
    {
        return (float) $this->changeOrders()->where('status', ChangeOrderStatus::Approved->value)->sum('amount');
    }

    public function plannedBudget(): float
    {
        return (float) $this->budget + $this->approvedChanges();
    }

    public function actual(): float
    {
        return (float) $this->costEntries()->where('kind', CostEntryKind::Actual->value)->sum('amount');
    }

    public function estimated(): float
    {
        return (float) $this->costEntries()->where('kind', CostEntryKind::Estimate->value)->sum('amount');
    }

    public function variance(): float
    {
        return $this->plannedBudget() - $this->actual();
    }
}
