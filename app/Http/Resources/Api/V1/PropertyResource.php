<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Property
 */
class PropertyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'property_number' => $this->property_number,
            'manager_id' => $this->manager_id,
            'street' => $this->street,
            'zip_code' => $this->zip_code,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'gross_area' => $this->gross_area,
            'net_area' => $this->net_area,
            'cleanable_area' => $this->cleanable_area,
            'heated_volume' => $this->heated_volume,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'energy_rating' => $this->energy_rating?->value,
            'energy_cert_valid_until' => $this->energy_cert_valid_until?->format('Y-m-d'),
            'manager' => new UserResource($this->whenLoaded('manager')),
            'structure_nodes' => StructureNodeResource::collection($this->whenLoaded('structureNodes')),
            'assets' => AssetResource::collection($this->whenLoaded('assets')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'links' => [
                'self' => route('api.v1.properties.show', $this->id),
            ],
        ];
    }
}
