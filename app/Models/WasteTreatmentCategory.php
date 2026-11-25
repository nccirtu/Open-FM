<?php

namespace App\Models;

use App\Enums\WasteEsrsCategory;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\WasteTreatmentCategoryFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WasteTreatmentCategory extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<WasteTreatmentCategoryFactory> */
    use HasFactory;

    use HasUuids;

    protected $table = 'waste_treatment_categories';

    protected $fillable = [
        'key',
        'name',
        'is_recovery',
        'counts_as_recycling',
        'esrs_category',
        'color',
        'sort',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_recovery' => 'boolean',
            'counts_as_recycling' => 'boolean',
            'esrs_category' => WasteEsrsCategory::class,
            'sort' => 'integer',
        ];
    }

    public function treatments(): HasMany
    {
        return $this->hasMany(WasteTreatment::class);
    }
}
