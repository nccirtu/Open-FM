<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Asset;
use App\Support\Uploads;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Asset
 */
class AssetResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'asset_number' => $this->asset_number,
            'name' => $this->name,
            'property_id' => $this->property_id,
            'structure_node_id' => $this->structure_node_id,
            'asset_type_id' => $this->asset_type_id,
            'parent_asset_id' => $this->parent_asset_id,
            'company_id' => $this->company_id,
            'inspection_company_id' => $this->inspection_company_id,
            'manufacturer' => $this->manufacturer,
            'serial_number' => $this->serial_number,
            'condition' => $this->condition?->value,
            'service_status' => $this->service_status?->value,
            'installed_at' => $this->installed_at?->format('Y-m-d'),
            'decommissioned_at' => $this->decommissioned_at?->format('Y-m-d'),
            'warranty_until' => $this->warranty_until?->format('Y-m-d'),
            'purchase_price' => $this->purchase_price,
            'last_maintenance_at' => $this->last_maintenance_at?->format('Y-m-d'),
            'next_maintenance_at' => $this->next_maintenance_at?->format('Y-m-d'),
            'ifc_guid' => $this->ifc_guid,
            'image' => $this->image !== null ? Uploads::url($this->image) : null,
            'property' => new PropertyResource($this->whenLoaded('property')),
            'structure_node' => new StructureNodeResource($this->whenLoaded('structureNode')),
            'asset_type' => new AssetTypeResource($this->whenLoaded('assetType')),
            'company' => new CompanyResource($this->whenLoaded('company')),
            'inspection_company' => new CompanyResource($this->whenLoaded('inspectionCompany')),
            'parent_asset' => new AssetResource($this->whenLoaded('parentAsset')),
            'child_assets' => AssetResource::collection($this->whenLoaded('childAssets')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'links' => [
                'self' => route('api.v1.assets.show', $this->id),
            ],
        ];
    }
}
