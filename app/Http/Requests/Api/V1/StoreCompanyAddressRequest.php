<?php

namespace App\Http\Requests\Api\V1;

use App\Models\CompanyAddress;
use Illuminate\Validation\Rule;

class StoreCompanyAddressRequest extends ApiFormRequest
{
    public const TYPES = ['billing', 'shipping', 'site', 'other'];

    public function authorize(): bool
    {
        return $this->allows('create', CompanyAddress::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['nullable', Rule::in(self::TYPES)],
            'label' => ['nullable', 'string', 'max:255'],
            'street' => ['nullable', 'string', 'max:255'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'size:2'],
            'is_primary' => ['nullable', 'boolean'],
        ];
    }
}
