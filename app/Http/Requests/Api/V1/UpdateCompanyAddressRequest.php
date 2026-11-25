<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Validation\Rule;

class UpdateCompanyAddressRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowsThroughParent('update', $this->route('address'), 'company');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'required', Rule::in(StoreCompanyAddressRequest::TYPES)],
            'label' => ['sometimes', 'nullable', 'string', 'max:255'],
            'street' => ['sometimes', 'nullable', 'string', 'max:255'],
            'zip_code' => ['sometimes', 'nullable', 'string', 'max:20'],
            'city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'state' => ['sometimes', 'nullable', 'string', 'max:255'],
            'country' => ['sometimes', 'nullable', 'string', 'size:2'],
            'is_primary' => ['sometimes', 'nullable', 'boolean'],
        ];
    }
}
