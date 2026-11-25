<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\ProjectHealth;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Support\ApiContext;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allows('update', $this->route('project'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = ApiContext::organizationId();

        return [
            'property_id' => ['sometimes', 'nullable', 'uuid', Rule::exists('properties', 'id')->where('organization_id', $organizationId)],
            'manager_id' => ['sometimes', 'nullable', 'uuid', Rule::exists('organization_user', 'user_id')->where('organization_id', $organizationId)],
            'code' => ['sometimes', 'nullable', 'string', 'max:50'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'type' => ['sometimes', 'nullable', Rule::enum(ProjectType::class)],
            'status' => ['sometimes', 'required', Rule::enum(ProjectStatus::class)],
            'health' => ['sometimes', 'required', Rule::enum(ProjectHealth::class)],
            'start_date' => ['sometimes', 'nullable', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date'],
            'budget' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'property_id.exists' => __('api.validation.property_not_found'),
            'manager_id.exists' => __('api.validation.user_not_found'),
        ];
    }
}
