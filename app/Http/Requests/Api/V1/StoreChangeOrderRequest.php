<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\ChangeOrderStatus;
use App\Models\ChangeOrder;
use App\Support\ApiContext;
use Illuminate\Validation\Rule;

class StoreChangeOrderRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allows('create', ChangeOrder::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'project_id' => ['required', 'uuid', Rule::exists('projects', 'id')->where('organization_id', ApiContext::organizationId())],
            'budget_position_id' => ['nullable', 'uuid', Rule::exists('budget_positions', 'id')->where('project_id', $this->input('project_id'))],
            'title' => ['required', 'string', 'max:255'],
            'reason' => ['nullable', 'string'],
            'amount' => ['required', 'numeric'],
            'status' => ['nullable', Rule::enum(ChangeOrderStatus::class)],
            'requested_on' => ['nullable', 'date'],
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
