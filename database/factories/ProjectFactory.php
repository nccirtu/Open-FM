<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'code' => 'P-'.fake()->unique()->numberBetween(1000, 9999),
            'name' => fake()->words(3, true),
            'type' => ProjectType::Remodel,
            'status' => ProjectStatus::Active,
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->addDays(60)->toDateString(),
            'budget' => 100000,
        ];
    }

    public function managedBy(User $user): static
    {
        return $this->state(fn (): array => ['manager_id' => $user->getKey()]);
    }
}
