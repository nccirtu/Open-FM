<?php

namespace Database\Factories;

use App\Models\WasteTreatmentCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WasteTreatmentCategory>
 */
class WasteTreatmentCategoryFactory extends Factory
{
    protected $model = WasteTreatmentCategory::class;

    public function definition(): array
    {
        return [
            'name' => 'Recycling',
            'is_recovery' => true,
            'counts_as_recycling' => true,
        ];
    }
}
