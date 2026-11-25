<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\CostEntryKind;
use App\Support\ApiContext;
use Illuminate\Validation\Rule;

class UpdateCostEntryRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allows('update', $this->route('cost_entry'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = ApiContext::organizationId();
        $projectId = $this->input('project_id', $this->route('cost_entry')?->project_id);

        return [
            'project_id' => ['sometimes', 'required', 'uuid', Rule::exists('projects', 'id')->where('organization_id', $organizationId)],
            'budget_position_id' => ['sometimes', 'nullable', 'uuid', Rule::exists('budget_positions', 'id')->where('project_id', $projectId)],
            'cost_center_id' => ['sometimes', 'nullable', 'uuid', Rule::exists('classification_nodes', 'id')->where('organization_id', $organizationId)],
            'company_id' => ['sometimes', 'nullable', 'uuid', Rule::exists('companies', 'id')->where('organization_id', $organizationId)],
            'kind' => ['sometimes', 'required', Rule::enum(CostEntryKind::class)],
            'receipt_type' => ['sometimes', 'required', Rule::in(StoreCostEntryRequest::RECEIPT_TYPES)],
            'amount' => ['sometimes', 'required', 'numeric'],
            'tax_rate' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
            'net_amount' => ['sometimes', 'nullable', 'numeric'],
            'invoice_number' => ['sometimes', 'nullable', 'string', 'max:100'],
            'booked_on' => ['sometimes', 'required', 'date'],
            'due_on' => ['sometimes', 'nullable', 'date'],
            'vendor' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
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
            'cost_center_id.exists' => __('api.validation.cost_center_not_found'),
            'company_id.exists' => __('api.validation.company_not_found'),
        ];
    }
}
