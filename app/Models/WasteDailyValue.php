<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WasteDailyValue extends Model
{
    use BelongsToOrganization;
    use HasUuids;

    protected $table = 'waste_daily_values';

    protected $fillable = [
        'property_id',
        'waste_fraction_id',
        'date',
        'waste_treatment_category_id',
        'is_construction',
        'mass_kg',
        'collections',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_construction' => 'boolean',
            'mass_kg' => 'decimal:2',
            'collections' => 'integer',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function treatmentCategory(): BelongsTo
    {
        return $this->belongsTo(WasteTreatmentCategory::class, 'waste_treatment_category_id');
    }

    public function fraction(): BelongsTo
    {
        return $this->belongsTo(WasteFraction::class, 'waste_fraction_id');
    }
}
