<?php

namespace App\Http\Resources\Api\V1;

use App\Models\MaintenancePlan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MaintenancePlan
 */
class MaintenancePlanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'asset_id' => $this->asset_id,
            'maintenance_plan_template_id' => $this->maintenance_plan_template_id,
            'name' => $this->name,
            'type' => $this->type?->value,
            'legal_basis' => $this->legal_basis,
            'interval_value' => $this->interval_value,
            'interval_unit' => $this->interval_unit?->value,
            'next_due_at' => $this->next_due_at?->format('Y-m-d'),
            'lead_time_days' => $this->lead_time_days,
            'company_id' => $this->company_id,
            'inspection_company_id' => $this->inspection_company_id,
            'contract_id' => $this->contract_id,
            'is_active' => $this->is_active,
            'notes' => $this->notes,
            'tasks' => $this->tasks,
            'asset' => new AssetResource($this->whenLoaded('asset')),
            'company' => new CompanyResource($this->whenLoaded('company')),
            'inspection_company' => new CompanyResource($this->whenLoaded('inspectionCompany')),
            'contract' => new ContractResource($this->whenLoaded('contract')),
            'orders' => MaintenanceOrderResource::collection($this->whenLoaded('orders')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'links' => [
                'self' => route('api.v1.maintenance-plans.show', $this->id),
            ],
        ];
    }
}
