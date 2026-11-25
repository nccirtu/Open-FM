<?php

namespace App\Models;

use App\Enums\EnergyMedium;
use App\Enums\MeterKind;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\MeterFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

/**
 * @property array<int, array{meter_id: string, sign: int}>|null $formula
 */
class Meter extends Model
{
    /** @use HasFactory<MeterFactory> */
    use BelongsToOrganization, HasFactory;

    use HasRecursiveRelationships;
    use HasUuids;

    protected $table = 'meters';

    protected $fillable = [
        'property_id',
        'parent_id',
        'asset_id',
        'structure_node_id',
        'name',
        'number',
        'medium',
        'kind',
        'unit',
        'factor',
        'formula',
        'market_location_id',
        'calibration_valid_until',
        'is_active',
        'notes',
    ];

    protected $attributes = [
        'kind' => 'sub',
        'unit' => 'kWh',
        'factor' => 1,
        'is_active' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'medium' => EnergyMedium::class,
            'kind' => MeterKind::class,
            'factor' => 'decimal:6',
            'formula' => 'array',
            'calibration_valid_until' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function structureNode(): BelongsTo
    {
        return $this->belongsTo(StructureNode::class);
    }

    public function suppliedNodes(): BelongsToMany
    {
        return $this->belongsToMany(StructureNode::class)->withPivot('share');
    }

    public function measuredAssets(): BelongsToMany
    {
        return $this->belongsToMany(Asset::class)->withPivot('share');
    }

    public function readings(): HasMany
    {
        return $this->hasMany(MeterReading::class);
    }

    public function latestReading(): HasOne
    {
        return $this->hasOne(MeterReading::class)->latestOfMany('read_at');
    }

    public function dailyValues(): HasMany
    {
        return $this->hasMany(MeterDailyValue::class);
    }

    public function isVirtual(): bool
    {
        return $this->kind === MeterKind::Virtual;
    }

    public function evaluationUnit(): string
    {
        return $this->medium?->evaluationUnit() ?? 'kWh';
    }

    /**
     * @return array<int, string>
     */
    public function formulaMeterIds(): array
    {
        return array_values(array_filter(array_map(
            fn (mixed $term): ?string => is_array($term) ? ($term['meter_id'] ?? null) : null,
            $this->formula ?? [],
        )));
    }

    public function calibrationColor(): string
    {
        if ($this->calibration_valid_until === null) {
            return 'gray';
        }

        return match (true) {
            $this->calibration_valid_until->isPast() => 'danger',
            $this->calibration_valid_until->lessThanOrEqualTo(now()->addDays(180)) => 'warning',
            default => 'success',
        };
    }

    /**
     * @param  Builder<Meter>  $query
     * @return Builder<Meter>
     */
    public function scopeTopLevel(Builder $query): Builder
    {
        return $query->whereNull('parent_id')->where('kind', '!=', MeterKind::Virtual->value);
    }

    public function selectLabel(): string
    {
        $label = $this->name.($this->number ? ' ('.$this->number.')' : '');

        return $this->property ? $label.' · '.$this->property->property_number : $label;
    }
}
