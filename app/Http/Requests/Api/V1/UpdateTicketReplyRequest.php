<?php

namespace App\Http\Requests\Api\V1;

class UpdateTicketReplyRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allows('update', $this->route('reply'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'message' => ['sometimes', 'required', 'string'],
            'is_internal_note' => ['sometimes', 'boolean'],
        ];
    }
}
