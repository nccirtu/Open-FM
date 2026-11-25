<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\WasteEmissionFactorFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WasteEmissionFactor extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<WasteEmissionFactorFactory> */
    use HasFactory;

    use HasUuids;

    protected $table = 'waste_emission_factors';

    protected $fillable = [
        'waste_fraction_id',
        'waste_treatment_category_id',
        'kg_co2e_per_t',
        'source',
        'valid_year',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kg_co2e_per_t' => 'decimal:2',
            'valid_year' => 'integer',
        ];
    }

    public function fraction(): BelongsTo
    {
        return $this->belongsTo(WasteFraction::class, 'waste_fraction_id');
    }

    public function treatmentCategory(): BelongsTo
    {
        return $this->belongsTo(WasteTreatmentCategory::class, 'waste_treatment_category_id');
    }
}
