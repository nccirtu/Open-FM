<?php

namespace App\Http\Resources\Api\V1;

use App\Models\WasteFraction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WasteFraction
 */
class WasteFractionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'is_hazardous' => $this->is_hazardous,
            'density_kg_m3' => $this->density_kg_m3 !== null ? (float) $this->density_kg_m3 : null,
        ];
    }
}
