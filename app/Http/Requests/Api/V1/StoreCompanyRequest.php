<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\CompanyRole;
use App\Models\Company;
use App\Support\ApiContext;
use Illuminate\Validation\Rule;

class StoreCompanyRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allows('create', Company::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('companies', 'name')->where('organization_id', ApiContext::organizationId())],
            'role' => ['nullable', Rule::enum(CompanyRole::class)],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'vat_id' => ['nullable', 'string', 'max:30'],
            'tax_number' => ['nullable', 'string', 'max:30'],
            'iban' => ['nullable', 'string', 'max:40'],
            'bic' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => __('api.validation.company_name_taken'),
        ];
    }
}
