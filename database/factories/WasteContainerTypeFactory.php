<?php

namespace Database\Factories;

use App\Models\WasteContainerType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WasteContainerType>
 */
class WasteContainerTypeFactory extends Factory
{
    protected $model = WasteContainerType::class;

    public function definition(): array
    {
        return [
            'name' => 'Müllgroßbehälter',
            'default_volume_l' => 1100,
        ];
    }
}
