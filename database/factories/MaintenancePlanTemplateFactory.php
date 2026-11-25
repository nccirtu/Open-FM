<?php

namespace Database\Factories;

use App\Enums\MaintenanceIntervalUnit;
use App\Enums\MaintenanceType;
use App\Models\AssetType;
use App\Models\MaintenancePlanTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenancePlanTemplate>
 */
class MaintenancePlanTemplateFactory extends Factory
{
    protected $model = MaintenancePlanTemplate::class;

    public function definition(): array
    {
        return [
            'asset_type_id' => fn (): string => AssetType::query()->firstOrCreate(['name' => 'Heizung'])->id,
            'country' => 'DE',
            'name' => fake()->unique()->sentence(3),
            'type' => MaintenanceType::Inspection,
            'legal_basis' => 'DGUV V3',
            'interval_value' => 12,
            'interval_unit' => MaintenanceIntervalUnit::Months,
            'lead_time_days' => 14,
            'is_active' => true,
        ];
    }
}
