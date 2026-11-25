<?php

namespace Database\Factories;

use App\Models\WasteTreatment;
use App\Models\WasteTreatmentCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WasteTreatment>
 */
class WasteTreatmentFactory extends Factory
{
    protected $model = WasteTreatment::class;

    public function definition(): array
    {
        return [
            'waste_treatment_category_id' => WasteTreatmentCategory::factory(),
            'code' => 'R'.fake()->numberBetween(1, 13),
            'name' => 'Recycling',
        ];
    }
}
