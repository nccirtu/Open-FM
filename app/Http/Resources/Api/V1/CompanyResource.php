<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Company
 */
class CompanyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'role' => $this->role?->value,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'vat_id' => $this->vat_id,
            'tax_number' => $this->tax_number,
            'iban' => $this->iban,
            'bic' => $this->bic,
            'notes' => $this->notes,
            'addresses' => CompanyAddressResource::collection($this->whenLoaded('addresses')),
            'contacts' => CompanyContactResource::collection($this->whenLoaded('contacts')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'links' => [
                'self' => route('api.v1.companies.show', $this->id),
            ],
        ];
    }
}
