<?php

namespace Database\Factories;

use App\Enums\WasteGoalType;
use App\Models\WasteGoal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WasteGoal>
 */
class WasteGoalFactory extends Factory
{
    protected $model = WasteGoal::class;

    public function definition(): array
    {
        return [
            'name' => 'Recyclingquote',
            'type' => WasteGoalType::MinRecyclingRate,
            'year' => (int) now()->year,
            'target_value' => 70,
        ];
    }
}
