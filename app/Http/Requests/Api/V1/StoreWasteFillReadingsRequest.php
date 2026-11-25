<?php

namespace App\Http\Requests\Api\V1;

use App\Support\ApiContext;
use Illuminate\Validation\Rule;

class StoreWasteFillReadingsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update_waste::container') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = ApiContext::organizationId();

        return [
            'readings' => ['required', 'array', 'min:1', 'max:1000'],
            'readings.*.container_id' => ['nullable', 'uuid', 'required_without:readings.*.container_number', Rule::exists('waste_containers', 'id')->where('organization_id', $organizationId)->where('is_active', true)],
            'readings.*.container_number' => ['nullable', 'string', 'max:255', 'required_without:readings.*.container_id', Rule::exists('waste_containers', 'number')->where('organization_id', $organizationId)->where('is_active', true)],
            'readings.*.measured_at' => ['required', 'date'],
            'readings.*.fill_level_pct' => ['required', 'integer', 'between:0,100'],
            'readings.*.temperature_c' => ['nullable', 'numeric', 'between:-60,150'],
            'readings.*.sensor_id' => ['nullable', 'string', 'max:100'],
            'readings.*.pickup_detected' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'readings.*.container_id.exists' => __('api.validation.container_not_found'),
            'readings.*.container_number.exists' => __('api.validation.container_not_found'),
        ];
    }
}
