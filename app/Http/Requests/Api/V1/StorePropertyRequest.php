<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\EnergyRating;
use App\Models\Property;
use App\Support\ApiContext;
use Illuminate\Validation\Rule;

class StorePropertyRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allows('create', Property::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'property_number' => ['required', 'string', 'max:255', Rule::unique('properties', 'property_number')->where('organization_id', ApiContext::organizationId())],
            'manager_id' => ['nullable', 'uuid', Rule::exists('organization_user', 'user_id')->where('organization_id', ApiContext::organizationId())],
            'street' => ['nullable', 'string', 'max:255'],
            'zip_code' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'gross_area' => ['nullable', 'numeric', 'min:0'],
            'net_area' => ['nullable', 'numeric', 'min:0'],
            'cleanable_area' => ['nullable', 'numeric', 'min:0'],
            'heated_volume' => ['nullable', 'numeric', 'min:0'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'energy_rating' => ['nullable', Rule::enum(EnergyRating::class)],
            'energy_cert_valid_until' => ['nullable', 'date'],
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
