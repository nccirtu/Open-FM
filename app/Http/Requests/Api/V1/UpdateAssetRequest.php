<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\AssetCondition;
use App\Enums\AssetServiceStatus;
use App\Support\ApiContext;
use Illuminate\Validation\Rule;

class UpdateAssetRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allows('update', $this->route('asset'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = ApiContext::organizationId();

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'property_id' => ['sometimes', 'required', 'uuid', Rule::exists('properties', 'id')->where('organization_id', $organizationId)],
            'structure_node_id' => ['sometimes', 'nullable', 'uuid', Rule::exists('structure_nodes', 'id')->where('organization_id', $organizationId)],
            'asset_type_id' => ['sometimes', 'required', 'uuid', Rule::exists('asset_types', 'id')],
            'parent_asset_id' => ['sometimes', 'nullable', 'uuid', Rule::exists('assets', 'id')->where('organization_id', $organizationId), Rule::notIn([$this->route('asset')?->getKey()])],
            'company_id' => ['sometimes', 'nullable', 'uuid', Rule::exists('companies', 'id')->where('organization_id', $organizationId)],
            'inspection_company_id' => ['sometimes', 'nullable', 'uuid', Rule::exists('companies', 'id')->where('organization_id', $organizationId)],
            'manufacturer' => ['sometimes', 'nullable', 'string', 'max:255'],
            'serial_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'condition' => ['sometimes', 'nullable', Rule::enum(AssetCondition::class)],
            'service_status' => ['sometimes', 'nullable', Rule::enum(AssetServiceStatus::class)],
            'installed_at' => ['sometimes', 'nullable', 'date'],
            'decommissioned_at' => ['sometimes', 'nullable', 'date'],
            'warranty_until' => ['sometimes', 'nullable', 'date'],
            'purchase_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'last_maintenance_at' => ['sometimes', 'nullable', 'date'],
            'next_maintenance_at' => ['sometimes', 'nullable', 'date'],
            'ifc_guid' => ['sometimes', 'nullable', 'string', 'max:64'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'property_id.exists' => __('api.validation.property_not_found'),
            'structure_node_id.exists' => __('api.validation.structure_node_not_found'),
            'company_id.exists' => __('api.validation.company_not_found'),
            'inspection_company_id.exists' => __('api.validation.company_not_found'),
            'parent_asset_id.exists' => __('api.validation.asset_not_found'),
            'parent_asset_id.not_in' => __('api.validation.asset_parent_self'),
        ];
    }
}
