<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\MaintenanceOrderStatus;
use App\Support\ApiContext;
use Illuminate\Validation\Rule;

class UpdateMaintenanceOrderRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allows('update', $this->route('maintenance_order'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = ApiContext::organizationId();

        return [
            'maintenance_plan_id' => ['sometimes', 'required', 'uuid', Rule::exists('maintenance_plans', 'id')->where('organization_id', $organizationId)],
            'asset_id' => ['sometimes', 'required', 'uuid', Rule::exists('assets', 'id')->where('organization_id', $organizationId)],
            'due_at' => ['sometimes', 'required', 'date'],
            'status' => ['sometimes', Rule::enum(MaintenanceOrderStatus::class)],
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
            'maintenance_plan_id.exists' => __('api.validation.maintenance_plan_not_found'),
            'asset_id.exists' => __('api.validation.asset_not_found'),
        ];
    }
}
