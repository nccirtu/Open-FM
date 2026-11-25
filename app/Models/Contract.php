<?php

namespace App\Models;

use App\Enums\BillingCycle;
use App\Enums\ContractStatus;
use App\Enums\ContractType;
use App\Flow\Concerns\HasWorkflows;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\ContractFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;
use Slimani\MediaManager\Concerns\InteractsWithMediaFiles;

class Contract extends Model
{
    /** @use HasFactory<ContractFactory> */
    use BelongsToOrganization, HasFactory;

    use HasUuids;
    use HasWorkflows;
    use InteractsWithMediaFiles;

    protected $fillable = [
        'name',
        'contract_number',
        'partner_name',
        'company_id',
        'type',
        'status',
        'start_date',
        'end_date',
        'notice_period_days',
        'reminder_lead_days',
        'reminded_at',
        'amount',
        'billing_cycle',
        'notes',
    ];

    protected $attributes = [
        'type' => 'service',
        'status' => 'active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ContractType::class,
            'status' => ContractStatus::class,
            'billing_cycle' => BillingCycle::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'reminded_at' => 'datetime',
            'amount' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class);
    }

    public function assets(): BelongsToMany
    {
        return $this->belongsToMany(Asset::class);
    }

    public function files(): MorphToMany
    {
        return $this->mediaFiles('files');
    }

    public function costEntries(): MorphMany
    {
        return $this->morphMany(CostEntry::class, 'costable');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function maintenancePlans(): HasMany
    {
        return $this->hasMany(MaintenancePlan::class);
    }

    public function noticeDeadline(): ?Carbon
    {
        if ($this->end_date === null) {
            return null;
        }

        return $this->end_date->copy()->subDays($this->notice_period_days ?? 0);
    }

    public function noticeDeadlineColor(): string
    {
        $deadline = $this->noticeDeadline();

        if ($deadline === null || $this->status !== ContractStatus::Active) {
            return 'gray';
        }

        return match (true) {
            $deadline->isPast() || $deadline->lessThanOrEqualTo(now()->addDays(30)) => 'danger',
            $deadline->lessThanOrEqualTo(now()->addDays($this->reminder_lead_days)) => 'warning',
            default => 'success',
        };
    }

    public function yearlyAmount(): float
    {
        if ($this->amount === null) {
            return 0.0;
        }

        return match ($this->billing_cycle) {
            BillingCycle::Monthly => (float) $this->amount * 12,
            BillingCycle::Quarterly => (float) $this->amount * 4,
            BillingCycle::Yearly => (float) $this->amount,
            default => 0.0,
        };
    }

    /**
     * @param  Builder<Contract>  $query
     * @return Builder<Contract>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ContractStatus::Active);
    }

    public function needsTerminationReminder(): bool
    {
        $deadline = $this->noticeDeadline();

        return $this->status === ContractStatus::Active
            && $deadline !== null
            && $deadline->lessThanOrEqualTo(now()->addDays($this->reminder_lead_days));
    }
}
