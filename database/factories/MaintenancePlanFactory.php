<?php

namespace Database\Factories;

use App\Enums\MaintenanceIntervalUnit;
use App\Enums\MaintenanceType;
use App\Models\Asset;
use App\Models\MaintenancePlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenancePlan>
 */
class MaintenancePlanFactory extends Factory
{
    protected $model = MaintenancePlan::class;

    public function definition(): array
    {
        return [
            'asset_id' => Asset::factory(),
            'name' => fake()->sentence(3),
            'type' => MaintenanceType::Inspection,
            'legal_basis' => 'DGUV V3',
            'interval_value' => 12,
            'interval_unit' => MaintenanceIntervalUnit::Months,
            'next_due_at' => now()->addMonths(3)->toDateString(),
            'lead_time_days' => 14,
            'is_active' => true,
        ];
    }
}
