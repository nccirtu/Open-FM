<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\AssetCondition;
use App\Enums\AssetServiceStatus;
use App\Models\Asset;
use App\Support\ApiContext;
use Illuminate\Validation\Rule;

class StoreAssetRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allows('create', Asset::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = ApiContext::organizationId();

        return [
            'name' => ['required', 'string', 'max:255'],
            'property_id' => ['required', 'uuid', Rule::exists('properties', 'id')->where('organization_id', $organizationId)],
            'structure_node_id' => ['nullable', 'uuid', Rule::exists('structure_nodes', 'id')->where('organization_id', $organizationId)],
            'asset_type_id' => ['required', 'uuid', Rule::exists('asset_types', 'id')],
            'parent_asset_id' => ['nullable', 'uuid', Rule::exists('assets', 'id')->where('organization_id', $organizationId)],
            'company_id' => ['nullable', 'uuid', Rule::exists('companies', 'id')->where('organization_id', $organizationId)],
            'inspection_company_id' => ['nullable', 'uuid', Rule::exists('companies', 'id')->where('organization_id', $organizationId)],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'condition' => ['nullable', Rule::enum(AssetCondition::class)],
            'service_status' => ['nullable', Rule::enum(AssetServiceStatus::class)],
            'installed_at' => ['nullable', 'date'],
            'decommissioned_at' => ['nullable', 'date', 'after_or_equal:installed_at'],
            'warranty_until' => ['nullable', 'date'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'last_maintenance_at' => ['nullable', 'date'],
            'next_maintenance_at' => ['nullable', 'date'],
            'ifc_guid' => ['nullable', 'string', 'max:64'],
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
        ];
    }
}
