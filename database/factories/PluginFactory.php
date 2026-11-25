<?php

namespace Database\Factories;

use App\Enums\PluginSource;
use App\Enums\PluginStatus;
use App\Models\Plugin;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Plugin>
 */
class PluginFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $slug = Str::slug(fake()->unique()->words(2, true));

        return [
            'slug' => $slug,
            'package' => "vendor/{$slug}",
            'name' => Str::headline($slug),
            'version' => '1.0.0',
            'source' => PluginSource::Composer,
            'status' => PluginStatus::Installed,
            'is_enabled' => true,
            'installed_at' => now(),
        ];
    }

    public function disabled(): static
    {
        return $this->state(['is_enabled' => false]);
    }
}
