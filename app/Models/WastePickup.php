<?php

namespace App\Models;

use App\Enums\WasteCollectionSource;
use App\Enums\WasteMissedReason;
use App\Enums\WastePickupKind;
use App\Enums\WastePickupStatus;
use App\Models\Concerns\BelongsToOrganization;
use Carbon\CarbonImmutable;
use Database\Factories\WastePickupFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WastePickup extends Model
{
    public const RECENT_DAYS = 7;

    use BelongsToOrganization;

    /** @use HasFactory<WastePickupFactory> */
    use HasFactory;

    use HasUuids;

    protected $table = 'waste_pickups';

    protected $fillable = [
        'waste_schedule_id',
        'waste_container_id',
        'property_id',
        'due_on',
        'kind',
        'free_of_charge',
        'status',
        'requested_at',
        'confirmed_at',
        'confirmed_via',
        'confirmed_by',
        'reminded_at',
        'staging_reminded_at',
        'missed_reason',
        'missed_note',
        'ticket_id',
    ];

    protected $attributes = [
        'kind' => 'regular',
        'status' => 'planned',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_on' => 'date',
            'kind' => WastePickupKind::class,
            'free_of_charge' => 'boolean',
            'status' => WastePickupStatus::class,
            'requested_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'confirmed_via' => WasteCollectionSource::class,
            'reminded_at' => 'datetime',
            'staging_reminded_at' => 'datetime',
            'missed_reason' => WasteMissedReason::class,
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(WasteSchedule::class, 'waste_schedule_id');
    }

    public function container(): BelongsTo
    {
        return $this->belongsTo(WasteContainer::class, 'waste_container_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function collection(): HasOne
    {
        return $this->hasOne(WasteCollection::class);
    }

    /**
     * @param  Builder<WastePickup>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', [WastePickupStatus::Requested->value, WastePickupStatus::Planned->value]);
    }

    /**
     * @param  Builder<WastePickup>  $query
     */
    public function scopeRecent(Builder $query, int $days = self::RECENT_DAYS, ?CarbonImmutable $today = null): void
    {
        $today ??= CarbonImmutable::today();

        $query
            ->whereDate('due_on', '<=', $today->toDateString())
            ->whereDate('due_on', '>=', $today->subDays($days)->toDateString())
            ->where(fn (Builder $query) => $query
                ->where('status', WastePickupStatus::Planned->value)
                ->orWhere(fn (Builder $query) => $query
                    ->where('status', WastePickupStatus::Confirmed->value)
                    ->where('confirmed_via', WasteCollectionSource::Schedule->value)));
    }

    public function statusLabel(): string
    {
        return $this->status->getLabel().($this->isCompletedByPlan() ? ' · '.__('waste.pickup_status.by_plan') : '');
    }

    public function isCompletedByPlan(): bool
    {
        return $this->status === WastePickupStatus::Confirmed && $this->confirmed_via === WasteCollectionSource::Schedule;
    }

    public function isDue(?CarbonImmutable $today = null): bool
    {
        return $this->status === WastePickupStatus::Planned
            && $this->due_on !== null
            && $this->due_on->lessThanOrEqualTo(($today ?? CarbonImmutable::today())->endOfDay());
    }

    public function cutoffAt(WasteSettings $settings): ?CarbonImmutable
    {
        if ($this->due_on === null) {
            return null;
        }

        [$hour, $minute] = array_map('intval', explode(':', $settings->cutoff_time.':0'));

        return CarbonImmutable::parse($this->due_on)->addDays($settings->cutoff_days)->setTime($hour, $minute);
    }
}
