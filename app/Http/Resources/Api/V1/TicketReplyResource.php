<?php

namespace App\Http\Resources\Api\V1;

use App\Models\TicketReply;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TicketReply
 */
class TicketReplyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_id' => $this->ticket_id,
            'user' => $this->whenLoaded('author', fn (): ?array => $this->author === null ? null : ['id' => $this->author->getKey(), 'name' => $this->author->name]),
            'message' => $this->message,
            'is_internal_note' => $this->is_internal_note,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'links' => [
                'self' => route('api.v1.replies.show', $this->id),
            ],
        ];
    }
}
