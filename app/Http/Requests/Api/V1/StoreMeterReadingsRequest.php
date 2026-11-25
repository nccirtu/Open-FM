<?php

namespace App\Http\Requests\Api\V1;

use App\Models\MeterReading;
use App\Support\ApiContext;
use Illuminate\Validation\Rule;

class StoreMeterReadingsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allows('create', MeterReading::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = ApiContext::organizationId();

        return [
            'readings' => ['required', 'array', 'min:1', 'max:1000'],
            'readings.*.meter_id' => ['nullable', 'uuid', 'required_without:readings.*.meter_number', Rule::exists('meters', 'id')->where('organization_id', $organizationId)->whereNot('kind', 'virtual')],
            'readings.*.meter_number' => ['nullable', 'string', 'max:255', 'required_without:readings.*.meter_id', Rule::exists('meters', 'number')->where('organization_id', $organizationId)->whereNot('kind', 'virtual')],
            'readings.*.read_at' => ['required', 'date'],
            'readings.*.value' => ['required', 'numeric', 'min:0'],
            'readings.*.note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'readings.*.meter_id.exists' => __('api.validation.meter_not_found'),
            'readings.*.meter_number.exists' => __('api.validation.meter_not_found'),
        ];
    }
}
