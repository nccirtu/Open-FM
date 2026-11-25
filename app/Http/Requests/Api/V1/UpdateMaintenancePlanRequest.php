<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\MaintenanceIntervalUnit;
use App\Enums\MaintenanceType;
use App\Support\ApiContext;
use Illuminate\Validation\Rule;

class UpdateMaintenancePlanRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allows('update', $this->route('maintenance_plan'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = ApiContext::organizationId();

        return [
            'asset_id' => ['sometimes', 'required', 'uuid', Rule::exists('assets', 'id')->where('organization_id', $organizationId)],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['sometimes', Rule::enum(MaintenanceType::class)],
            'legal_basis' => ['sometimes', 'nullable', 'string', 'max:255'],
            'interval_value' => ['sometimes', 'required', 'integer', 'min:1'],
            'interval_unit' => ['sometimes', Rule::enum(MaintenanceIntervalUnit::class)],
            'next_due_at' => ['sometimes', 'required', 'date'],
            'lead_time_days' => ['sometimes', 'integer', 'min:0'],
            'company_id' => ['sometimes', 'nullable', 'uuid', Rule::exists('companies', 'id')->where('organization_id', $organizationId)],
            'inspection_company_id' => ['sometimes', 'nullable', 'uuid', Rule::exists('companies', 'id')->where('organization_id', $organizationId)],
            'contract_id' => ['sometimes', 'nullable', 'uuid', Rule::exists('contracts', 'id')->where('organization_id', $organizationId)],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'tasks' => ['sometimes', 'nullable', 'array'],
            'tasks.*' => ['string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'asset_id.exists' => __('api.validation.asset_not_found'),
            'company_id.exists' => __('api.validation.company_not_found'),
            'inspection_company_id.exists' => __('api.validation.company_not_found'),
            'contract_id.exists' => __('api.validation.contract_not_found'),
        ];
    }
}
