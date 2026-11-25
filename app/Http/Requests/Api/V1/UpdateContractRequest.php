<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\BillingCycle;
use App\Enums\ContractStatus;
use App\Enums\ContractType;
use App\Support\ApiContext;
use Illuminate\Validation\Rule;

class UpdateContractRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allows('update', $this->route('contract'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = ApiContext::organizationId();

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'contract_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'partner_name' => ['sometimes', 'required', 'string', 'max:255'],
            'company_id' => ['sometimes', 'nullable', 'uuid', Rule::exists('companies', 'id')->where('organization_id', $organizationId)],
            'type' => ['sometimes', Rule::enum(ContractType::class)],
            'status' => ['sometimes', Rule::enum(ContractStatus::class)],
            'start_date' => ['sometimes', 'nullable', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date'],
            'notice_period_days' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'reminder_lead_days' => ['sometimes', 'integer', 'min:0'],
            'amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'billing_cycle' => ['sometimes', 'nullable', Rule::enum(BillingCycle::class)],
            'notes' => ['sometimes', 'nullable', 'string'],
            'property_ids' => ['sometimes', 'array'],
            'property_ids.*' => ['uuid', Rule::exists('properties', 'id')->where('organization_id', $organizationId)],
            'asset_ids' => ['sometimes', 'array'],
            'asset_ids.*' => ['uuid', Rule::exists('assets', 'id')->where('organization_id', $organizationId)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'company_id.exists' => __('api.validation.company_not_found'),
            'property_ids.*.exists' => __('api.validation.property_not_found'),
            'asset_ids.*.exists' => __('api.validation.asset_not_found'),
        ];
    }
}
