<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Contract
 */
class ContractResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'contract_number' => $this->contract_number,
            'partner_name' => $this->partner_name,
            'company_id' => $this->company_id,
            'type' => $this->type?->value,
            'status' => $this->status?->value,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'notice_period_days' => $this->notice_period_days,
            'notice_deadline' => $this->noticeDeadline()?->format('Y-m-d'),
            'reminder_lead_days' => $this->reminder_lead_days,
            'reminded_at' => $this->reminded_at?->toIso8601String(),
            'amount' => $this->amount,
            'billing_cycle' => $this->billing_cycle?->value,
            'notes' => $this->notes,
            'company' => new CompanyResource($this->whenLoaded('company')),
            'properties' => PropertyResource::collection($this->whenLoaded('properties')),
            'assets' => AssetResource::collection($this->whenLoaded('assets')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'links' => [
                'self' => route('api.v1.contracts.show', $this->id),
            ],
        ];
    }
}
