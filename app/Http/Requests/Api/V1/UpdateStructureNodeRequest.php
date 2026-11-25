<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\RoomUse;
use App\Enums\StructureNodeType;
use App\Models\StructureNode;
use App\Support\ApiContext;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateStructureNodeRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allows('update', $this->route('structure_node'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = ApiContext::organizationId();
        $node = $this->node();
        $propertyId = $this->input('property_id', $node?->property_id);

        return [
            'property_id' => ['sometimes', 'required', 'uuid', Rule::exists('properties', 'id')->where('organization_id', $organizationId)],
            'parent_id' => [
                'sometimes',
                'nullable',
                'uuid',
                Rule::notIn([$node?->getKey()]),
                Rule::exists('structure_nodes', 'id')->where('organization_id', $organizationId)->where('property_id', $propertyId),
            ],
            'type' => ['sometimes', 'required', Rule::enum(StructureNodeType::class)],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'sort' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'room_use' => ['sometimes', 'nullable', Rule::enum(RoomUse::class)],
            'area' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'height' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'heated' => ['sometimes', 'nullable', 'boolean'],
            'ifc_guid' => ['sometimes', 'nullable', 'string', 'max:64'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $parentId = $this->input('parent_id');
                $node = $this->node();

                if ($validator->errors()->has('parent_id') || $parentId === null || $node === null) {
                    return;
                }

                if ($node->descendants()->whereKey($parentId)->exists()) {
                    $validator->errors()->add('parent_id', __('api.validation.structure_node_parent_descendant'));
                }
            },
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
            'parent_id.not_in' => __('api.validation.structure_node_parent_self'),
        ];
    }

    private function node(): ?StructureNode
    {
        $node = $this->route('structure_node');

        return $node instanceof StructureNode ? $node : null;
    }
}
