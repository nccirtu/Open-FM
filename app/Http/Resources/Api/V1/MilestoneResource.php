<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Milestone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Milestone
 */
class MilestoneResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'phase_id' => $this->phase_id,
            'name' => $this->name,
            'due_date' => $this->due_date?->format('Y-m-d'),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'sort' => $this->sort,
            'project' => new ProjectResource($this->whenLoaded('project')),
            'phase' => new ProjectPhaseResource($this->whenLoaded('phase')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'links' => [
                'self' => route('api.v1.milestones.show', $this->id),
            ],
        ];
    }
}
