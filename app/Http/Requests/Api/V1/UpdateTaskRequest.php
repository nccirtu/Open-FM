<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Support\ApiContext;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allows('update', $this->route('task'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = ApiContext::organizationId();
        $task = $this->route('task');
        $projectId = $this->input('project_id', $task?->project_id);

        return [
            'project_id' => ['sometimes', 'required', 'uuid', Rule::exists('projects', 'id')->where('organization_id', $organizationId)],
            'phase_id' => ['sometimes', 'nullable', 'uuid', Rule::exists('project_phases', 'id')->where('project_id', $projectId)],
            'milestone_id' => ['sometimes', 'nullable', 'uuid', Rule::exists('milestones', 'id')->where('project_id', $projectId)],
            'assignee_id' => ['sometimes', 'nullable', 'uuid', Rule::exists('organization_user', 'user_id')->where('organization_id', $organizationId)],
            'blocked_by_task_id' => ['sometimes', 'nullable', 'uuid', Rule::exists('tasks', 'id')->where('organization_id', $organizationId), Rule::notIn([$task?->getKey()])],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'required', Rule::enum(TaskStatus::class)],
            'priority' => ['sometimes', 'required', Rule::enum(TaskPriority::class)],
            'trade' => ['sometimes', 'nullable', 'string', 'max:100'],
            'start_date' => ['sometimes', 'nullable', 'date'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'completed_at' => ['sometimes', 'nullable', 'date'],
            'position' => ['sometimes', 'nullable', 'numeric'],
            'checklist' => ['sometimes', 'nullable', 'array'],
            'checklist.*.text' => ['required', 'string', 'max:255'],
            'checklist.*.done' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'project_id.exists' => __('api.validation.project_not_found'),
            'phase_id.exists' => __('api.validation.phase_not_in_project'),
            'milestone_id.exists' => __('api.validation.milestone_not_in_project'),
            'assignee_id.exists' => __('api.validation.user_not_found'),
            'blocked_by_task_id.exists' => __('api.validation.task_not_found'),
            'blocked_by_task_id.not_in' => __('api.validation.task_blocked_by_self'),
        ];
    }
}
