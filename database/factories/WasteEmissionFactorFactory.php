<?php

namespace Database\Factories;

use App\Models\WasteEmissionFactor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WasteEmissionFactor>
 */
class WasteEmissionFactorFactory extends Factory
{
    protected $model = WasteEmissionFactor::class;

    public function definition(): array
    {
        return [
            'kg_co2e_per_t' => 21,
            'source' => 'Beispielwert',
            'valid_year' => (int) now()->year,
        ];
    }
}
