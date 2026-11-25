<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\RoomUse;
use App\Enums\StructureNodeType;
use App\Models\StructureNode;
use App\Support\ApiContext;
use Illuminate\Validation\Rule;

class StoreStructureNodeRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allows('create', StructureNode::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = ApiContext::organizationId();

        return [
            'property_id' => ['required', 'uuid', Rule::exists('properties', 'id')->where('organization_id', $organizationId)],
            'parent_id' => [
                'nullable',
                'uuid',
                Rule::exists('structure_nodes', 'id')->where('organization_id', $organizationId)->where('property_id', $this->input('property_id')),
            ],
            'type' => ['required', Rule::enum(StructureNodeType::class)],
            'name' => ['required', 'string', 'max:255'],
            'sort' => ['nullable', 'integer', 'min:0'],
            'room_use' => ['nullable', Rule::enum(RoomUse::class)],
            'area' => ['nullable', 'numeric', 'min:0'],
            'height' => ['nullable', 'numeric', 'min:0'],
            'heated' => ['nullable', 'boolean'],
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
            'parent_id.exists' => __('api.validation.structure_node_parent_property_mismatch'),
        ];
    }
}
