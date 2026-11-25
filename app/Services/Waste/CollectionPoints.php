<?php

namespace App\Services\Waste;

use App\Enums\RoomUse;
use App\Enums\StructureNodeType;
use App\Models\Property;
use App\Models\StructureNode;
use Illuminate\Database\Eloquent\Builder;

final class CollectionPoints
{
    public const TYPES = [StructureNodeType::Outdoor, StructureNodeType::Room, StructureNodeType::Zone];

    /**
     * @return Builder<StructureNode>
     */
    public function query(): Builder
    {
        return StructureNode::query()->where(fn (Builder $query) => $query
            ->where('room_use', RoomUse::WasteCollection->value)
            ->orWhereHas('wasteContainers'));
    }

    public function create(Property $property, string $name, StructureNodeType $type = StructureNodeType::Outdoor, ?string $parentId = null): StructureNode
    {
        return StructureNode::query()->create([
            'property_id' => $property->getKey(),
            'parent_id' => filled($parentId) ? $parentId : null,
            'type' => $type,
            'name' => $name,
            'room_use' => RoomUse::WasteCollection,
        ]);
    }

    /**
     * @return array<string, string>
     */
    public static function typeOptions(): array
    {
        return collect(self::TYPES)->mapWithKeys(fn (StructureNodeType $type): array => [$type->value => $type->getLabel()])->all();
    }
}
