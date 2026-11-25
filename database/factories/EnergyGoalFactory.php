<?php

namespace Database\Factories;

use App\Enums\EnergyGoalPeriod;
use App\Enums\EnergyGoalType;
use App\Enums\EnergyMedium;
use App\Models\EnergyGoal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EnergyGoal>
 */
class EnergyGoalFactory extends Factory
{
    protected $model = EnergyGoal::class;

    public function definition(): array
    {
        return [
            'name' => 'Strom '.now()->year,
            'medium' => EnergyMedium::Electricity,
            'type' => EnergyGoalType::MaxConsumption,
            'period' => EnergyGoalPeriod::Year,
            'year' => now()->year,
            'target_value' => 100000,
        ];
    }
}
