<?php

namespace App\Http\Requests\Api\V1;

use App\Support\ApiContext;
use Illuminate\Validation\Rule;

class UpdateBudgetPositionRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowsThroughParent('update', $this->route('budget_position'), 'project');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = ApiContext::organizationId();

        return [
            'project_id' => ['sometimes', 'required', 'uuid', Rule::exists('projects', 'id')->where('organization_id', $organizationId)],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'cost_group' => ['sometimes', 'nullable', 'string', 'max:50'],
            'cost_center_id' => ['sometimes', 'nullable', 'uuid', Rule::exists('classification_nodes', 'id')->where('organization_id', $organizationId)],
            'budget' => ['sometimes', 'nullable', 'numeric', 'min:0'],
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
            'cost_center_id.exists' => __('api.validation.cost_center_not_found'),
        ];
    }
}
