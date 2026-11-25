<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\ChangeOrderStatus;
use App\Support\ApiContext;
use Illuminate\Validation\Rule;

class UpdateChangeOrderRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowsThroughParent('update', $this->route('change_order'), 'project');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $projectId = $this->input('project_id', $this->route('change_order')?->project_id);

        return [
            'project_id' => ['sometimes', 'required', 'uuid', Rule::exists('projects', 'id')->where('organization_id', ApiContext::organizationId())],
            'budget_position_id' => ['sometimes', 'nullable', 'uuid', Rule::exists('budget_positions', 'id')->where('project_id', $projectId)],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'reason' => ['sometimes', 'nullable', 'string'],
            'amount' => ['sometimes', 'required', 'numeric'],
            'status' => ['sometimes', 'required', Rule::enum(ChangeOrderStatus::class)],
            'requested_on' => ['sometimes', 'nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'project_id.exists' => __('api.validation.project_not_found'),
            'budget_position_id.exists' => __('api.validation.budget_position_not_in_project'),
        ];
    }
}
