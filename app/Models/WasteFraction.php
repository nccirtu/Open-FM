<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\WasteFractionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WasteFraction extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<WasteFractionFactory> */
    use HasFactory;

    use HasUuids;

    protected $table = 'waste_fractions';

    protected $fillable = [
        'name',
        'code',
        'is_hazardous',
        'waste_fraction_group_id',
        'color',
        'density_kg_m3',
        'waste_treatment_id',
        'template_key',
        'is_active',
        'sort',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_hazardous' => 'boolean',
            'density_kg_m3' => 'decimal:2',
            'is_active' => 'boolean',
            'sort' => 'integer',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(WasteFractionGroup::class, 'waste_fraction_group_id');
    }

    public function treatment(): BelongsTo
    {
        return $this->belongsTo(WasteTreatment::class, 'waste_treatment_id');
    }

    public function containers(): HasMany
    {
        return $this->hasMany(WasteContainer::class);
    }

    public function collections(): HasMany
    {
        return $this->hasMany(WasteCollection::class);
    }

    /**
     * @param  Builder<WasteFraction>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function label(): string
    {
        return filled($this->code) ? "{$this->name} ({$this->code})" : $this->name;
    }

    public function displayColor(): string
    {
        return filled($this->color) ? $this->color : '#94a3b8';
    }
}
