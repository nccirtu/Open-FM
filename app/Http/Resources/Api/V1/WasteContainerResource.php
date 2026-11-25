<?php

namespace App\Http\Resources\Api\V1;

use App\Models\WasteContainer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WasteContainer
 */
class WasteContainerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'property_id' => $this->property_id,
            'structure_node_id' => $this->structure_node_id,
            'waste_fraction_id' => $this->waste_fraction_id,
            'company_id' => $this->company_id,
            'number' => $this->number,
            'type' => $this->type?->key,
            'volume_l' => $this->volume_l,
            'usual_fill_pct' => $this->usual_fill_pct,
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'is_active' => $this->is_active,
            'property' => new PropertyResource($this->whenLoaded('property')),
            'fraction' => new WasteFractionResource($this->whenLoaded('fraction')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'links' => [
                'self' => route('api.v1.waste-containers.show', $this->id),
            ],
        ];
    }
}
