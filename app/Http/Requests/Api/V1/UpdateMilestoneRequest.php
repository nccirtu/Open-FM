<?php

namespace App\Http\Requests\Api\V1;

use App\Support\ApiContext;
use Illuminate\Validation\Rule;

class UpdateMilestoneRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowsThroughParent('update', $this->route('milestone'), 'project');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $projectId = $this->input('project_id', $this->route('milestone')?->project_id);

        return [
            'project_id' => ['sometimes', 'required', 'uuid', Rule::exists('projects', 'id')->where('organization_id', ApiContext::organizationId())],
            'phase_id' => ['sometimes', 'nullable', 'uuid', Rule::exists('project_phases', 'id')->where('project_id', $projectId)],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'completed_at' => ['sometimes', 'nullable', 'date'],
            'sort' => ['sometimes', 'nullable', 'integer', 'min:0'],
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
        ];
    }
}
