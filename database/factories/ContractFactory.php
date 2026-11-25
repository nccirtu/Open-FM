<?php

namespace Database\Factories;

use App\Enums\BillingCycle;
use App\Enums\ContractStatus;
use App\Enums\ContractType;
use App\Models\Contract;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contract>
 */
class ContractFactory extends Factory
{
    protected $model = Contract::class;

    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'contract_number' => fake()->unique()->bothify('V-####'),
            'partner_name' => fake()->company(),
            'type' => ContractType::Service,
            'status' => ContractStatus::Active,
            'start_date' => now()->subYear()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'notice_period_days' => 90,
            'reminder_lead_days' => 60,
            'amount' => 1200,
            'billing_cycle' => BillingCycle::Monthly,
        ];
    }

    public function dueForTermination(): static
    {
        return $this->state(fn (): array => [
            'end_date' => now()->addDays(80)->toDateString(),
            'notice_period_days' => 90,
            'reminder_lead_days' => 60,
        ]);
    }
}
