<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\TicketCategory;
use App\Enums\TicketEstimateStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketSuspensionReason;
use App\Enums\TicketType;
use App\Support\ApiContext;
use Illuminate\Validation\Rule;

class UpdateTicketRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allows('update', $this->route('ticket'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = ApiContext::organizationId();

        return [
            'property_id' => ['sometimes', 'required', 'uuid', Rule::exists('properties', 'id')->where('organization_id', $organizationId)],
            'asset_id' => ['sometimes', 'nullable', 'uuid', Rule::exists('assets', 'id')->where('organization_id', $organizationId)],
            'structure_node_id' => ['sometimes', 'nullable', 'uuid', Rule::exists('structure_nodes', 'id')->where('organization_id', $organizationId)],
            'type' => ['sometimes', Rule::enum(TicketType::class)],
            'category' => ['sometimes', Rule::enum(TicketCategory::class)],
            'priority' => ['sometimes', Rule::enum(TicketPriority::class)],
            'status' => ['sometimes', Rule::enum(TicketStatus::class)],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'assigned_to' => ['sometimes', 'nullable', 'uuid', Rule::exists('organization_user', 'user_id')->where('organization_id', $organizationId)],
            'company_id' => ['sometimes', 'nullable', 'uuid', Rule::exists('companies', 'id')->where('organization_id', $organizationId)],
            'contract_id' => ['sometimes', 'nullable', 'uuid', Rule::exists('contracts', 'id')->where('organization_id', $organizationId)],
            'due_at' => ['sometimes', 'nullable', 'date'],
            'resolution_notes' => ['sometimes', 'nullable', 'string'],
            'suspension_reason' => ['sometimes', 'nullable', Rule::enum(TicketSuspensionReason::class)],
            'estimate_status' => ['sometimes', 'nullable', Rule::enum(TicketEstimateStatus::class)],
            'estimate_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'rating' => ['sometimes', 'nullable', 'integer', 'between:1,5'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'property_id.exists' => __('api.validation.property_not_found'),
            'asset_id.exists' => __('api.validation.asset_not_found'),
            'structure_node_id.exists' => __('api.validation.structure_node_not_found'),
            'assigned_to.exists' => __('api.validation.user_not_found'),
            'company_id.exists' => __('api.validation.company_not_found'),
            'contract_id.exists' => __('api.validation.contract_not_found'),
        ];
    }
}
