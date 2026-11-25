<?php

namespace Database\Factories;

use App\Enums\TicketPriority;
use App\Models\SlaDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SlaDefinition>
 */
class SlaDefinitionFactory extends Factory
{
    protected $model = SlaDefinition::class;

    public function definition(): array
    {
        return [
            'priority' => TicketPriority::P1,
            'response_hours' => 4,
            'resolution_hours' => 24,
            'is_active' => true,
        ];
    }
}
