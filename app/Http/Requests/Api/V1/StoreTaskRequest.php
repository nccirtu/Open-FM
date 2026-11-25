<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Support\ApiContext;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allows('create', Task::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = ApiContext::organizationId();
        $projectId = $this->input('project_id');

        return [
            'project_id' => ['required', 'uuid', Rule::exists('projects', 'id')->where('organization_id', $organizationId)],
            'phase_id' => ['nullable', 'uuid', Rule::exists('project_phases', 'id')->where('project_id', $projectId)],
            'milestone_id' => ['nullable', 'uuid', Rule::exists('milestones', 'id')->where('project_id', $projectId)],
            'assignee_id' => ['nullable', 'uuid', Rule::exists('organization_user', 'user_id')->where('organization_id', $organizationId)],
            'blocked_by_task_id' => ['nullable', 'uuid', Rule::exists('tasks', 'id')->where('organization_id', $organizationId)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', Rule::enum(TaskStatus::class)],
            'priority' => ['nullable', Rule::enum(TaskPriority::class)],
            'trade' => ['nullable', 'string', 'max:100'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'completed_at' => ['nullable', 'date'],
            'position' => ['nullable', 'numeric'],
            'checklist' => ['nullable', 'array'],
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
        ];
    }
}
