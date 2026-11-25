<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Task
 */
class TaskResource extends JsonResource
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
            'milestone_id' => $this->milestone_id,
            'assignee_id' => $this->assignee_id,
            'blocked_by_task_id' => $this->blocked_by_task_id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status?->value,
            'priority' => $this->priority?->value,
            'trade' => $this->trade,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'due_date' => $this->due_date?->format('Y-m-d'),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'position' => $this->position,
            'checklist' => $this->checklist,
            'created_by' => $this->created_by,
            'project' => new ProjectResource($this->whenLoaded('project')),
            'phase' => new ProjectPhaseResource($this->whenLoaded('phase')),
            'milestone' => new MilestoneResource($this->whenLoaded('milestone')),
            'assignee' => new UserResource($this->whenLoaded('assignee')),
            'blocked_by' => new TaskResource($this->whenLoaded('blockedBy')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'links' => [
                'self' => route('api.v1.tasks.show', $this->id),
            ],
        ];
    }
}
