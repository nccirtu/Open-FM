<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\CostEntryKind;
use App\Models\CostEntry;
use App\Support\ApiContext;
use Illuminate\Validation\Rule;

class StoreCostEntryRequest extends ApiFormRequest
{
    public const RECEIPT_TYPES = ['expense', 'income', 'credit_note'];

    public function authorize(): bool
    {
        return $this->allows('create', CostEntry::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = ApiContext::organizationId();

        return [
            'project_id' => ['required', 'uuid', Rule::exists('projects', 'id')->where('organization_id', $organizationId)],
            'budget_position_id' => ['nullable', 'uuid', Rule::exists('budget_positions', 'id')->where('project_id', $this->input('project_id'))],
            'cost_center_id' => ['nullable', 'uuid', Rule::exists('classification_nodes', 'id')->where('organization_id', $organizationId)],
            'company_id' => ['nullable', 'uuid', Rule::exists('companies', 'id')->where('organization_id', $organizationId)],
            'kind' => ['nullable', Rule::enum(CostEntryKind::class)],
            'receipt_type' => ['nullable', Rule::in(self::RECEIPT_TYPES)],
            'amount' => ['required', 'numeric'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'net_amount' => ['nullable', 'numeric'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'booked_on' => ['required', 'date'],
            'due_on' => ['nullable', 'date'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
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
