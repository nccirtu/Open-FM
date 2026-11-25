<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Asset;
use App\Models\Meter;
use App\Models\StructureNode;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Meter
 */
class MeterResource extends JsonResource
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
            'asset_id' => $this->asset_id,
            'structure_node_id' => $this->structure_node_id,
            'name' => $this->name,
            'number' => $this->number,
            'medium' => $this->medium?->value,
            'kind' => $this->kind?->value,
            'unit' => $this->unit,
            'factor' => $this->factor,
            'evaluation_unit' => $this->evaluationUnit(),
            'market_location_id' => $this->market_location_id,
            'calibration_valid_until' => $this->calibration_valid_until?->format('Y-m-d'),
            'is_active' => $this->is_active,
            'property' => new PropertyResource($this->whenLoaded('property')),
            'latest_reading' => new MeterReadingResource($this->whenLoaded('latestReading')),
            'measured_assets' => $this->whenLoaded('measuredAssets', fn () => $this->measuredAssets->map(fn (Asset $asset): array => [
                'id' => $asset->id,
                'name' => $asset->name,
                'share' => (float) $asset->pivot->share,
            ])),
            'supplied_nodes' => $this->whenLoaded('suppliedNodes', fn () => $this->suppliedNodes->map(fn (StructureNode $node): array => [
                'id' => $node->id,
                'name' => $node->name,
                'share' => (float) $node->pivot->share,
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'links' => [
                'self' => route('api.v1.meters.show', $this->id),
                'consumption' => route('api.v1.meters.consumption', $this->id),
            ],
        ];
    }
}
