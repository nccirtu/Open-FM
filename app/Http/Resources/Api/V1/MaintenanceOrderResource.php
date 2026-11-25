<?php

namespace App\Http\Resources\Api\V1;

use App\Models\MaintenanceOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MaintenanceOrder
 */
class MaintenanceOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'maintenance_plan_id' => $this->maintenance_plan_id,
            'asset_id' => $this->asset_id,
            'due_at' => $this->due_at?->format('Y-m-d'),
            'status' => $this->status?->value,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'completed_by_id' => $this->completed_by,
            'notes' => $this->notes,
            'tasks' => $this->tasks,
            'completed_tasks' => $this->completed_tasks,
            'plan' => new MaintenancePlanResource($this->whenLoaded('plan')),
            'asset' => new AssetResource($this->whenLoaded('asset')),
            'completed_by' => $this->whenLoaded('completedBy', fn (): ?array => $this->completedBy === null ? null : ['id' => $this->completedBy->getKey(), 'name' => $this->completedBy->name]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'links' => [
                'self' => route('api.v1.maintenance-orders.show', $this->id),
            ],
        ];
    }
}
