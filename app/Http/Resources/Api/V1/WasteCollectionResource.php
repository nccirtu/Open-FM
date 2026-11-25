<?php

namespace App\Http\Resources\Api\V1;

use App\Models\WasteCollection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WasteCollection
 */
class WasteCollectionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'waste_pickup_id' => $this->waste_pickup_id,
            'waste_container_id' => $this->waste_container_id,
            'property_id' => $this->property_id,
            'waste_fraction_id' => $this->waste_fraction_id,
            'project_id' => $this->project_id,
            'collected_at' => $this->collected_at?->toIso8601String(),
            'mass_kg' => $this->mass_kg !== null ? (float) $this->mass_kg : null,
            'mass_source' => $this->mass_source?->value,
            'fill_level_pct' => $this->fill_level_pct,
            'volume_l' => $this->volume_l,
            'treatment_code' => $this->treatment?->code,
            'source' => $this->source?->value,
            'document_number' => $this->document_number,
            'certificate_number' => $this->certificate_number,
            'note' => $this->note,
            'fraction' => new WasteFractionResource($this->whenLoaded('fraction')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
