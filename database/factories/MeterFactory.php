<?php

namespace Database\Factories;

use App\Enums\EnergyMedium;
use App\Enums\MeterKind;
use App\Models\Meter;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Meter>
 */
class MeterFactory extends Factory
{
    protected $model = Meter::class;

    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'name' => 'Stromzähler '.fake()->unique()->numberBetween(1, 9999),
            'number' => '1ESY'.fake()->unique()->numerify('##########'),
            'medium' => EnergyMedium::Electricity,
            'kind' => MeterKind::Sub,
            'unit' => 'kWh',
            'factor' => 1,
        ];
    }

    public function main(): static
    {
        return $this->state(['kind' => MeterKind::Main]);
    }

    public function medium(EnergyMedium $medium): static
    {
        return $this->state(['medium' => $medium, 'unit' => $medium->defaultUnit()]);
    }

    public function childOf(Meter $parent): static
    {
        return $this->state([
            'parent_id' => $parent->getKey(),
            'property_id' => $parent->property_id,
            'medium' => $parent->medium,
            'unit' => $parent->unit,
        ]);
    }
}
