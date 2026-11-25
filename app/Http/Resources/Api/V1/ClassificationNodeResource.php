<?php

namespace App\Http\Resources\Api\V1;

use App\Models\ClassificationNode;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ClassificationNode
 */
class ClassificationNodeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'code' => $this->code,
            'name' => $this->name,
            'label' => $this->label(),
            'sort' => $this->sort,
            'parent' => new ClassificationNodeResource($this->whenLoaded('parent')),
            'children' => ClassificationNodeResource::collection($this->whenLoaded('children')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'links' => [
                'self' => route('api.v1.classification-nodes.show', $this->id),
            ],
        ];
    }
}
