<?php

namespace App\Http\Resources\Api\V1;

use App\Models\BudgetPosition;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BudgetPosition
 */
class BudgetPositionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'name' => $this->name,
            'cost_group' => $this->cost_group,
            'cost_center_id' => $this->cost_center_id,
            'budget' => $this->budget,
            'sort' => $this->sort,
            'project' => new ProjectResource($this->whenLoaded('project')),
            'cost_center' => new ClassificationNodeResource($this->whenLoaded('costCenter')),
            'cost_entries' => CostEntryResource::collection($this->whenLoaded('costEntries')),
            'change_orders' => ChangeOrderResource::collection($this->whenLoaded('changeOrders')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'links' => [
                'self' => route('api.v1.budget-positions.show', $this->id),
            ],
        ];
    }
}
