<?php

namespace App\Http\Resources\Api\V1;

use App\Models\ChangeOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ChangeOrder
 */
class ChangeOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'budget_position_id' => $this->budget_position_id,
            'title' => $this->title,
            'reason' => $this->reason,
            'amount' => $this->amount,
            'status' => $this->status?->value,
            'requested_on' => $this->requested_on?->format('Y-m-d'),
            'decided_at' => $this->decided_at?->toIso8601String(),
            'decided_by' => $this->decided_by,
            'project' => new ProjectResource($this->whenLoaded('project')),
            'budget_position' => new BudgetPositionResource($this->whenLoaded('budgetPosition')),
            'decider' => new UserResource($this->whenLoaded('decider')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'links' => [
                'self' => route('api.v1.change-orders.show', $this->id),
            ],
        ];
    }
}
