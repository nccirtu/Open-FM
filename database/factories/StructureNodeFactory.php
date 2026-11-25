<?php

namespace Database\Factories;

use App\Enums\RoomUse;
use App\Enums\StructureNodeType;
use App\Models\Property;
use App\Models\StructureNode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StructureNode>
 */
class StructureNodeFactory extends Factory
{
    protected $model = StructureNode::class;

    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'type' => StructureNodeType::Room,
            'name' => 'Verkaufsraum',
            'room_use' => RoomUse::SalesFloor,
            'area' => 450,
            'heated' => true,
        ];
    }

    public function building(string $name = 'Gebäude A'): static
    {
        return $this->state([
            'type' => StructureNodeType::Building,
            'name' => $name,
            'room_use' => null,
            'area' => null,
            'heated' => null,
        ]);
    }

    public function floor(int $level = 0, string $name = 'Erdgeschoss'): static
    {
        return $this->state([
            'type' => StructureNodeType::Floor,
            'name' => $name,
            'level' => $level,
            'room_use' => null,
            'area' => null,
            'heated' => null,
        ]);
    }
}
