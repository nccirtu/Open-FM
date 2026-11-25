<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketReply>
 */
class TicketReplyFactory extends Factory
{
    protected $model = TicketReply::class;

    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'message' => fake()->paragraph(),
            'is_internal_note' => false,
        ];
    }
}
