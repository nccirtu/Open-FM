<?php

namespace App\Http\Resources\Api\V1;

use App\Models\StructureNode;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StructureNode
 */
class StructureNodeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'property_id' => $this->property_id,
            'parent_id' => $this->parent_id,
            'type' => $this->type?->value,
            'name' => $this->name,
            'sort' => $this->sort,
            'room_use' => $this->room_use?->value,
            'area' => $this->area,
            'height' => $this->height,
            'heated' => $this->heated,
            'ifc_guid' => $this->ifc_guid,
            'property' => new PropertyResource($this->whenLoaded('property')),
            'parent' => new StructureNodeResource($this->whenLoaded('parent')),
            'children' => StructureNodeResource::collection($this->whenLoaded('children')),
            'assets' => AssetResource::collection($this->whenLoaded('assets')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'links' => [
                'self' => route('api.v1.structure-nodes.show', $this->id),
            ],
        ];
    }
}
