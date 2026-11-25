<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\ProjectPhaseStatus;
use App\Models\ProjectPhase;
use App\Support\ApiContext;
use Illuminate\Validation\Rule;

class StoreProjectPhaseRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allows('create', ProjectPhase::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'project_id' => ['required', 'uuid', Rule::exists('projects', 'id')->where('organization_id', ApiContext::organizationId())],
            'name' => ['required', 'string', 'max:255'],
            'sort' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::enum(ProjectPhaseStatus::class)],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'project_id.exists' => __('api.validation.project_not_found'),
        ];
    }
}
