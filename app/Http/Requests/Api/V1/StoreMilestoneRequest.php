<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Milestone;
use App\Support\ApiContext;
use Illuminate\Validation\Rule;

class StoreMilestoneRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allows('create', Milestone::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'project_id' => ['required', 'uuid', Rule::exists('projects', 'id')->where('organization_id', ApiContext::organizationId())],
            'phase_id' => ['nullable', 'uuid', Rule::exists('project_phases', 'id')->where('project_id', $this->input('project_id'))],
            'name' => ['required', 'string', 'max:255'],
            'due_date' => ['nullable', 'date'],
            'completed_at' => ['nullable', 'date'],
            'sort' => ['nullable', 'integer', 'min:0'],
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
