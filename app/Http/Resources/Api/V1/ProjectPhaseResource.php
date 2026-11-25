<?php

namespace App\Http\Resources\Api\V1;

use App\Models\ProjectPhase;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProjectPhase
 */
class ProjectPhaseResource extends JsonResource
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
            'sort' => $this->sort,
            'status' => $this->status?->value,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'project' => new ProjectResource($this->whenLoaded('project')),
            'milestones' => MilestoneResource::collection($this->whenLoaded('milestones')),
            'tasks' => TaskResource::collection($this->whenLoaded('tasks')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'links' => [
                'self' => route('api.v1.project-phases.show', $this->id),
            ],
        ];
    }
}
