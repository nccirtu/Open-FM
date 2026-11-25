<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Ticket
 */
class TicketResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_number' => $this->ticket_number,
            'property_id' => $this->property_id,
            'asset_id' => $this->asset_id,
            'structure_node_id' => $this->structure_node_id,
            'type' => $this->type?->value,
            'category' => $this->category?->value,
            'priority' => $this->priority?->value,
            'status' => $this->status?->value,
            'title' => $this->title,
            'description' => $this->description,
            'reported_by' => $this->reported_by,
            'assigned_to' => $this->assigned_to,
            'company_id' => $this->company_id,
            'contract_id' => $this->contract_id,
            'due_at' => $this->due_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'resolution_notes' => $this->resolution_notes,
            'respond_due_at' => $this->respond_due_at?->toIso8601String(),
            'resolve_due_at' => $this->resolve_due_at?->toIso8601String(),
            'first_response_at' => $this->first_response_at?->toIso8601String(),
            'sla_escalated_at' => $this->sla_escalated_at?->toIso8601String(),
            'suspended_at' => $this->suspended_at?->toIso8601String(),
            'suspension_reason' => $this->suspension_reason?->value,
            'estimate_status' => $this->estimate_status?->value,
            'estimate_amount' => $this->estimate_amount,
            'rating' => $this->rating,
            'last_activity_at' => $this->last_activity_at?->toIso8601String(),
            'property' => new PropertyResource($this->whenLoaded('property')),
            'asset' => new AssetResource($this->whenLoaded('asset')),
            'structure_node' => new StructureNodeResource($this->whenLoaded('structureNode')),
            'reporter' => $this->whenLoaded('reporter', fn (): ?array => $this->reporter === null ? null : ['id' => $this->reporter->getKey(), 'name' => $this->reporter->name]),
            'assignee' => $this->whenLoaded('assignee', fn (): ?array => $this->assignee === null ? null : ['id' => $this->assignee->getKey(), 'name' => $this->assignee->name]),
            'company' => new CompanyResource($this->whenLoaded('company')),
            'contract' => new ContractResource($this->whenLoaded('contract')),
            'replies' => TicketReplyResource::collection($this->whenLoaded('replies')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'links' => [
                'self' => route('api.v1.tickets.show', $this->id),
            ],
        ];
    }
}
