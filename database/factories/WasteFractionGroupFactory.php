<?php

namespace Database\Factories;

use App\Models\WasteFractionGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WasteFractionGroup>
 */
class WasteFractionGroupFactory extends Factory
{
    protected $model = WasteFractionGroup::class;

    public function definition(): array
    {
        return [
            'name' => 'Papier',
            'counts_as_separated' => true,
        ];
    }
}
