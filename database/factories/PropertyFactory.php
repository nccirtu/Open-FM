<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Property>
 */
class PropertyFactory extends Factory
{
    protected $model = Property::class;

    public function definition(): array
    {
        return [
            'property_number' => (string) fake()->unique()->numberBetween(10000, 99999),
            'city' => fake()->city(),
            'country' => 'DE',
        ];
    }

    public function managedBy(User $user): static
    {
        return $this->state(fn (): array => ['manager_id' => $user->getKey()]);
    }
}
