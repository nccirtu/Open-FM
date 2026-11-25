<?php

namespace Database\Factories;

use App\Enums\MaintenanceOrderStatus;
use App\Models\MaintenanceOrder;
use App\Models\MaintenancePlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceOrder>
 */
class MaintenanceOrderFactory extends Factory
{
    protected $model = MaintenanceOrder::class;

    public function definition(): array
    {
        return [
            'maintenance_plan_id' => MaintenancePlan::factory(),
            'asset_id' => fn (array $attributes): string => MaintenancePlan::query()->findOrFail($attributes['maintenance_plan_id'])->asset_id,
            'due_at' => now()->addWeek()->toDateString(),
            'status' => MaintenanceOrderStatus::Open,
        ];
    }
}
