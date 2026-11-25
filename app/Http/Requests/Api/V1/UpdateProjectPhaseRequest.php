<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\ProjectPhaseStatus;
use App\Support\ApiContext;
use Illuminate\Validation\Rule;

class UpdateProjectPhaseRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowsThroughParent('update', $this->route('project_phase'), 'project');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'project_id' => ['sometimes', 'required', 'uuid', Rule::exists('projects', 'id')->where('organization_id', ApiContext::organizationId())],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'sort' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'status' => ['sometimes', 'required', Rule::enum(ProjectPhaseStatus::class)],
            'start_date' => ['sometimes', 'nullable', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'project_id.exists' => __('api.validation.project_not_found'),
        ];
    }
}
