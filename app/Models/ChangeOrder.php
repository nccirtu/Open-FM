<?php

namespace App\Models;

use App\Enums\ChangeOrderStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChangeOrder extends Model
{
    use HasUuids;

    protected $fillable = ['project_id', 'budget_position_id', 'title', 'reason', 'amount', 'status', 'requested_on', 'decided_at', 'decided_by'];

    protected $attributes = [
        'status' => 'pending',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ChangeOrderStatus::class,
            'amount' => 'decimal:2',
            'requested_on' => 'date',
            'decided_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function budgetPosition(): BelongsTo
    {
        return $this->belongsTo(BudgetPosition::class);
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function decide(ChangeOrderStatus $status, int|string|null $userId = null): void
    {
        $this->forceFill(['status' => $status, 'decided_at' => now(), 'decided_by' => $userId])->save();
    }
}
