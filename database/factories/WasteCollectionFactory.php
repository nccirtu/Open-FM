<?php

namespace Database\Factories;

use App\Enums\WasteCollectionSource;
use App\Enums\WasteMassSource;
use App\Models\Property;
use App\Models\WasteCollection;
use App\Models\WasteFraction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WasteCollection>
 */
class WasteCollectionFactory extends Factory
{
    protected $model = WasteCollection::class;

    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'waste_fraction_id' => WasteFraction::factory(),
            'collected_at' => now(),
            'mass_kg' => 120,
            'mass_source' => WasteMassSource::Measured,
            'source' => WasteCollectionSource::Manual,
        ];
    }
}
