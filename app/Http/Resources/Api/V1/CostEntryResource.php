<?php

namespace App\Http\Resources\Api\V1;

use App\Models\CostEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CostEntry
 */
class CostEntryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'costable_type' => $this->costable_type,
            'costable_id' => $this->costable_id,
            'budget_position_id' => $this->budget_position_id,
            'cost_center_id' => $this->cost_center_id,
            'company_id' => $this->company_id,
            'kind' => $this->kind?->value,
            'receipt_type' => $this->receipt_type,
            'amount' => $this->amount,
            'tax_rate' => $this->tax_rate,
            'net_amount' => $this->net_amount,
            'invoice_number' => $this->invoice_number,
            'booked_on' => $this->booked_on?->format('Y-m-d'),
            'due_on' => $this->due_on?->format('Y-m-d'),
            'vendor' => $this->vendor,
            'description' => $this->description,
            'has_receipt' => $this->receipt !== null,
            'created_by' => $this->created_by,
            'project' => new ProjectResource($this->whenLoaded('project')),
            'budget_position' => new BudgetPositionResource($this->whenLoaded('budgetPosition')),
            'cost_center' => new ClassificationNodeResource($this->whenLoaded('costCenter')),
            'company' => new CompanyResource($this->whenLoaded('company')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'links' => [
                'self' => route('api.v1.cost-entries.show', $this->id),
            ],
        ];
    }
}
