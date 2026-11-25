<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'asset_type_id' => fn (): string => AssetType::query()->firstOrCreate(['name' => 'Heizung'])->id,
            'name' => fake()->words(2, true),
        ];
    }
}
