<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\EnergyRating;
use App\Support\ApiContext;
use Illuminate\Validation\Rule;

class UpdatePropertyRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allows('update', $this->route('property'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'property_number' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('properties', 'property_number')->where('organization_id', ApiContext::organizationId())->ignore($this->route('property'))],
            'manager_id' => ['sometimes', 'nullable', 'uuid', Rule::exists('organization_user', 'user_id')->where('organization_id', ApiContext::organizationId())],
            'street' => ['sometimes', 'nullable', 'string', 'max:255'],
            'zip_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'state' => ['sometimes', 'nullable', 'string', 'max:255'],
            'country' => ['sometimes', 'nullable', 'string', 'max:255'],
            'gross_area' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'net_area' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'cleanable_area' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'heated_volume' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'energy_rating' => ['sometimes', 'nullable', Rule::enum(EnergyRating::class)],
            'energy_cert_valid_until' => ['sometimes', 'nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'property_number.unique' => __('api.validation.property_number_taken'),
            'manager_id.exists' => __('api.validation.user_not_found'),
        ];
    }
}
