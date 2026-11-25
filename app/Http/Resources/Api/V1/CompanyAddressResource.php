<?php

namespace App\Http\Resources\Api\V1;

use App\Models\CompanyAddress;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CompanyAddress
 */
class CompanyAddressResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'type' => $this->type,
            'label' => $this->label,
            'street' => $this->street,
            'zip_code' => $this->zip_code,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'is_primary' => (bool) $this->is_primary,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'links' => [
                'self' => route('api.v1.addresses.show', $this->id),
            ],
        ];
    }
}
