<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\CompanyRole;
use App\Support\ApiContext;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allows('update', $this->route('company'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('companies', 'name')->where('organization_id', ApiContext::organizationId())->ignore($this->route('company'))],
            'role' => ['sometimes', 'required', Rule::enum(CompanyRole::class)],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'website' => ['sometimes', 'nullable', 'url:http,https', 'max:255'],
            'vat_id' => ['sometimes', 'nullable', 'string', 'max:30'],
            'tax_number' => ['sometimes', 'nullable', 'string', 'max:30'],
            'iban' => ['sometimes', 'nullable', 'string', 'max:40'],
            'bic' => ['sometimes', 'nullable', 'string', 'max:20'],
            'notes' => ['sometimes', 'nullable', 'string'],
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
