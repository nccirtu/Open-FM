<?php

namespace App\Models;

use App\Enums\WasteIntervalUnit;
use App\Enums\WasteScheduleSource;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\WasteScheduleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property array<int, string>|null $confirmation_channels
 * @property array<int, string>|null $ics_dates
 */
class WasteSchedule extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<WasteScheduleFactory> */
    use HasFactory;

    use HasUuids;

    protected $table = 'waste_schedules';

    protected $fillable = [
        'property_id',
        'structure_node_id',
        'name',
        'source',
        'interval_value',
        'interval_unit',
        'weekday',
        'week_of_month',
        'window_from',
        'window_to',
        'needs_staging',
        'confirmation_channels',
        'ics_dates',
        'ics_filter',
        'starts_on',
        'ends_on',
        'is_active',
        'notes',
    ];

    protected $attributes = [
        'source' => 'rule',
        'interval_value' => 1,
        'interval_unit' => 'week',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source' => WasteScheduleSource::class,
            'interval_value' => 'integer',
            'interval_unit' => WasteIntervalUnit::class,
            'weekday' => 'integer',
            'week_of_month' => 'integer',
            'needs_staging' => 'boolean',
            'confirmation_channels' => 'array',
            'ics_dates' => 'array',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function structureNode(): BelongsTo
    {
        return $this->belongsTo(StructureNode::class);
    }

    public function containers(): BelongsToMany
    {
        return $this->belongsToMany(WasteContainer::class, 'waste_container_waste_schedule');
    }

    public function pickups(): HasMany
    {
        return $this->hasMany(WastePickup::class);
    }

    /**
     * @param  Builder<WasteSchedule>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function ruleLabel(): string
    {
        if ($this->source !== WasteScheduleSource::Rule) {
            return $this->source->getLabel();
        }

        if ($this->interval_unit === WasteIntervalUnit::Once) {
            return __('waste.schedule.rule_once', ['date' => $this->starts_on?->format('d.m.Y')]);
        }

        if ($this->interval_unit === WasteIntervalUnit::Year) {
            return trans_choice('waste.schedule.rule_year', $this->interval_value, [
                'count' => $this->interval_value,
                'date' => $this->starts_on?->format('d.m.'),
            ]);
        }

        $weekday = $this->weekday !== null ? __('waste.weekdays.'.$this->weekday) : '';

        if ($this->interval_unit === WasteIntervalUnit::Month) {
            $week = $this->week_of_month === -1 ? __('waste.schedule.last') : $this->week_of_month.'.';

            return trans_choice('waste.schedule.rule_month', $this->interval_value, [
                'count' => $this->interval_value,
                'week' => $week,
                'weekday' => $weekday,
            ]);
        }

        return trans_choice('waste.schedule.rule_week', $this->interval_value, [
            'count' => $this->interval_value,
            'weekday' => $weekday,
        ]);
    }
}
