<?php

namespace Database\Factories;

use App\Models\BimModel;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BimModel>
 */
class BimModelFactory extends Factory
{
    protected $model = BimModel::class;

    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'name' => 'Gebäudemodell.ifc',
            'file_path' => 'bim-models/'.fake()->uuid().'.ifc',
        ];
    }
}
