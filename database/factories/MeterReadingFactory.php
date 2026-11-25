<?php

namespace Database\Factories;

use App\Enums\MeterReadingSource;
use App\Enums\MeterReadingStatus;
use App\Models\Meter;
use App\Models\MeterReading;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeterReading>
 */
class MeterReadingFactory extends Factory
{
    protected $model = MeterReading::class;

    public function definition(): array
    {
        return [
            'meter_id' => Meter::factory(),
            'read_at' => now()->startOfHour(),
            'value' => fake()->randomFloat(1, 1000, 90000),
            'source' => MeterReadingSource::Manual,
            'status' => MeterReadingStatus::Plausible,
        ];
    }
}
