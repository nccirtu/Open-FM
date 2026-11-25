<?php

namespace App\Http\Requests\Api\V1;

use App\Models\BudgetPosition;
use App\Support\ApiContext;
use Illuminate\Validation\Rule;

class StoreBudgetPositionRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allows('create', BudgetPosition::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = ApiContext::organizationId();

        return [
            'project_id' => ['required', 'uuid', Rule::exists('projects', 'id')->where('organization_id', $organizationId)],
            'name' => ['required', 'string', 'max:255'],
            'cost_group' => ['nullable', 'string', 'max:50'],
            'cost_center_id' => ['nullable', 'uuid', Rule::exists('classification_nodes', 'id')->where('organization_id', $organizationId)],
            'budget' => ['nullable', 'numeric', 'min:0'],
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
            'cost_center_id.exists' => __('api.validation.cost_center_not_found'),
        ];
    }
}
