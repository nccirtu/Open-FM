<?php

namespace App\Http\Requests\Api\V1;

use App\Models\TicketReply;

class StoreTicketReplyRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allows('create', TicketReply::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string'],
            'is_internal_note' => ['sometimes', 'boolean'],
        ];
    }
}
