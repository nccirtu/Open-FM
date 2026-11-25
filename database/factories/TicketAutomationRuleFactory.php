<?php

namespace Database\Factories;

use App\Models\TicketAutomationRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketAutomationRule>
 */
class TicketAutomationRuleFactory extends Factory
{
    protected $model = TicketAutomationRule::class;

    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'is_active' => true,
            'event' => TicketAutomationRule::EVENT_CREATED,
            'conditions' => [],
            'actions' => [],
        ];
    }
}
