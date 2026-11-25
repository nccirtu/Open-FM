<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\ProjectHealth;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Models\Project;
use App\Support\ApiContext;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allows('create', Project::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = ApiContext::organizationId();

        return [
            'property_id' => ['nullable', 'uuid', Rule::exists('properties', 'id')->where('organization_id', $organizationId)],
            'manager_id' => ['nullable', 'uuid', Rule::exists('organization_user', 'user_id')->where('organization_id', $organizationId)],
            'code' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['nullable', Rule::enum(ProjectType::class)],
            'status' => ['nullable', Rule::enum(ProjectStatus::class)],
            'health' => ['nullable', Rule::enum(ProjectHealth::class)],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'budget' => ['nullable', 'numeric', 'min:0'],
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
