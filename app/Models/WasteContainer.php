<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\WasteContainerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class WasteContainer extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<WasteContainerFactory> */
    use HasFactory;

    use HasUuids;

    protected $table = 'waste_containers';

    protected $fillable = [
        'property_id',
        'structure_node_id',
        'waste_fraction_id',
        'company_id',
        'contract_id',
        'waste_container_type_id',
        'number',
        'volume_l',
        'usual_fill_pct',
        'price_per_pickup',
        'price_per_tonne',
        'monthly_rent',
        'latitude',
        'longitude',
        'ifc_guid',
        'is_active',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'volume_l' => 'integer',
            'usual_fill_pct' => 'integer',
            'price_per_pickup' => 'decimal:2',
            'price_per_tonne' => 'decimal:2',
            'monthly_rent' => 'decimal:2',
            'latitude' => 'float',
            'longitude' => 'float',
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

    public function fraction(): BelongsTo
    {
        return $this->belongsTo(WasteFraction::class, 'waste_fraction_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(WasteContainerType::class, 'waste_container_type_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function schedules(): BelongsToMany
    {
        return $this->belongsToMany(WasteSchedule::class, 'waste_container_waste_schedule');
    }

    public function pickups(): HasMany
    {
        return $this->hasMany(WastePickup::class);
    }

    public function collections(): HasMany
    {
        return $this->hasMany(WasteCollection::class);
    }

    public function fillReadings(): HasMany
    {
        return $this->hasMany(WasteFillReading::class);
    }

    public function costEntries(): MorphMany
    {
        return $this->morphMany(CostEntry::class, 'costable');
    }

    /**
     * @param  Builder<WasteContainer>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function label(): string
    {
        return trim($this->number.' · '.($this->fraction?->name ?? '').' '.number_format($this->volume_l, 0, ',', '.').' l');
    }
}
