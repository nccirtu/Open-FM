<?php

namespace Database\Factories;

use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Models\Property;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'type' => TicketType::Fault,
            'category' => fake()->randomElement(TicketCategory::cases()),
            'priority' => TicketPriority::P3,
            'status' => TicketStatus::Reported,
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
        ];
    }
}
