<?php

namespace App\Models;

use App\Enums\CostEntryKind;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Slimani\MediaManager\Concerns\InteractsWithMediaFiles;

class CostEntry extends Model
{
    use BelongsToOrganization;
    use HasUuids;
    use InteractsWithMediaFiles;

    protected $fillable = ['project_id', 'costable_type', 'costable_id', 'budget_position_id', 'cost_center_id', 'company_id', 'kind', 'receipt_type', 'amount', 'tax_rate', 'net_amount', 'invoice_number', 'booked_on', 'due_on', 'vendor', 'description', 'receipt', 'created_by'];

    protected $attributes = [
        'kind' => 'actual',
        'receipt_type' => 'expense',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => CostEntryKind::class,
            'amount' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'booked_on' => 'date',
            'due_on' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (CostEntry $entry) => $entry->created_by ??= auth()->id());
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function budgetPosition(): BelongsTo
    {
        return $this->belongsTo(BudgetPosition::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(ClassificationNode::class, 'cost_center_id');
    }

    public function costable(): MorphTo
    {
        return $this->morphTo();
    }
}
