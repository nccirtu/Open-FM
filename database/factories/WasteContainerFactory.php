<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\WasteContainer;
use App\Models\WasteFraction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WasteContainer>
 */
class WasteContainerFactory extends Factory
{
    protected $model = WasteContainer::class;

    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'waste_fraction_id' => WasteFraction::factory(),
            'number' => 'B-'.fake()->unique()->numerify('####'),
            'volume_l' => 1100,
            'is_active' => true,
        ];
    }
}
