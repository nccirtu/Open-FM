<?php

namespace Database\Factories;

use App\Enums\WastePickupKind;
use App\Enums\WastePickupStatus;
use App\Models\WasteContainer;
use App\Models\WastePickup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WastePickup>
 */
class WastePickupFactory extends Factory
{
    protected $model = WastePickup::class;

    public function definition(): array
    {
        return [
            'waste_container_id' => WasteContainer::factory(),
            'property_id' => fn (array $attributes): string => WasteContainer::query()->withoutGlobalScopes()->findOrFail($attributes['waste_container_id'])->property_id,
            'due_on' => today()->toDateString(),
            'kind' => WastePickupKind::Regular,
            'status' => WastePickupStatus::Planned,
        ];
    }

    public function forContainer(WasteContainer $container): static
    {
        return $this->state([
            'waste_container_id' => $container->getKey(),
            'property_id' => $container->property_id,
        ]);
    }
}
