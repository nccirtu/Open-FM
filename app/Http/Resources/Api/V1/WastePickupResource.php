<?php

namespace App\Http\Resources\Api\V1;

use App\Models\WastePickup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WastePickup
 */
class WastePickupResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'waste_container_id' => $this->waste_container_id,
            'waste_schedule_id' => $this->waste_schedule_id,
            'property_id' => $this->property_id,
            'due_on' => $this->due_on?->format('Y-m-d'),
            'kind' => $this->kind?->value,
            'status' => $this->status?->value,
            'free_of_charge' => $this->free_of_charge,
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'confirmed_via' => $this->confirmed_via?->value,
            'missed_reason' => $this->missed_reason?->value,
            'container' => new WasteContainerResource($this->whenLoaded('container')),
            'collection' => new WasteCollectionResource($this->whenLoaded('collection')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
