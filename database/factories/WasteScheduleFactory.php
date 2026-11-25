<?php

namespace Database\Factories;

use App\Enums\WasteIntervalUnit;
use App\Enums\WasteScheduleSource;
use App\Models\Property;
use App\Models\WasteSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WasteSchedule>
 */
class WasteScheduleFactory extends Factory
{
    protected $model = WasteSchedule::class;

    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'source' => WasteScheduleSource::Rule,
            'interval_value' => 1,
            'interval_unit' => WasteIntervalUnit::Week,
            'weekday' => 2,
            'starts_on' => now()->startOfWeek()->toDateString(),
            'is_active' => true,
        ];
    }
}
