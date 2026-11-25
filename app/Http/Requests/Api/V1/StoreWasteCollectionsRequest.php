<?php

namespace App\Http\Requests\Api\V1;

use App\Models\WasteCollection;
use App\Support\ApiContext;
use Illuminate\Validation\Rule;

class StoreWasteCollectionsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allows('create', WasteCollection::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = ApiContext::organizationId();

        return [
            'collections' => ['required', 'array', 'min:1', 'max:1000'],
            'collections.*.container_id' => ['nullable', 'uuid', Rule::exists('waste_containers', 'id')->where('organization_id', $organizationId)->where('is_active', true)],
            'collections.*.container_number' => ['nullable', 'string', 'max:255', Rule::exists('waste_containers', 'number')->where('organization_id', $organizationId)->where('is_active', true)],
            'collections.*.property_id' => ['nullable', 'uuid', 'required_with:collections.*.fraction_id,collections.*.fraction_code', Rule::exists('properties', 'id')->where('organization_id', $organizationId)],
            'collections.*.fraction_id' => ['nullable', 'uuid', Rule::exists('waste_fractions', 'id')->where('organization_id', $organizationId)],
            'collections.*.fraction_code' => ['nullable', 'string', 'max:20', Rule::exists('waste_fractions', 'code')->where('organization_id', $organizationId)],
            'collections.*.collected_at' => ['required', 'date'],
            'collections.*.mass_kg' => ['nullable', 'numeric', 'min:0', 'required_with:collections.*.property_id'],
            'collections.*.fill_level_pct' => ['nullable', 'integer', 'between:0,100'],
            'collections.*.treatment_code' => ['nullable', 'string', 'max:10', Rule::exists('waste_treatments', 'code')->where('organization_id', $organizationId)],
            'collections.*.document_number' => ['nullable', 'string', 'max:100'],
            'collections.*.certificate_number' => ['nullable', 'string', 'max:100'],
            'collections.*.note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function ($validator): void {
                foreach ((array) $this->input('collections', []) as $index => $row) {
                    $hasContainer = filled($row['container_id'] ?? null) || filled($row['container_number'] ?? null);
                    $hasProperty = filled($row['property_id'] ?? null) && (filled($row['fraction_id'] ?? null) || filled($row['fraction_code'] ?? null));

                    if ($hasContainer === $hasProperty) {
                        $validator->errors()->add("collections.{$index}", __('api.validation.collection_target'));
                    }
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
            'collections.*.container_id.exists' => __('api.validation.container_not_found'),
            'collections.*.container_number.exists' => __('api.validation.container_not_found'),
            'collections.*.property_id.exists' => __('api.validation.property_not_found'),
            'collections.*.fraction_id.exists' => __('api.validation.fraction_not_found'),
            'collections.*.fraction_code.exists' => __('api.validation.fraction_not_found'),
            'collections.*.treatment_code.exists' => __('api.validation.treatment_not_found'),
        ];
    }
}
