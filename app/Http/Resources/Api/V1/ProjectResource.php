<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Project
 */
class ProjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'property_id' => $this->property_id,
            'manager_id' => $this->manager_id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type?->value,
            'status' => $this->status?->value,
            'health' => $this->health?->value,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'budget' => $this->budget,
            'created_by' => $this->created_by,
            'property' => new PropertyResource($this->whenLoaded('property')),
            'manager' => new UserResource($this->whenLoaded('manager')),
            'phases' => ProjectPhaseResource::collection($this->whenLoaded('phases')),
            'milestones' => MilestoneResource::collection($this->whenLoaded('milestones')),
            'tasks' => TaskResource::collection($this->whenLoaded('tasks')),
            'budget_positions' => BudgetPositionResource::collection($this->whenLoaded('budgetPositions')),
            'cost_entries' => CostEntryResource::collection($this->whenLoaded('costEntries')),
            'change_orders' => ChangeOrderResource::collection($this->whenLoaded('changeOrders')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'links' => [
                'self' => route('api.v1.projects.show', $this->id),
            ],
        ];
    }
}
