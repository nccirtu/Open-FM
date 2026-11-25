<?php

namespace App\Http\Resources\Api\V1;

use App\Models\SlaDefinition;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SlaDefinition
 */
class SlaDefinitionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'priority' => $this->priority?->value,
            'response_hours' => $this->response_hours,
            'resolution_hours' => $this->resolution_hours,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'links' => [
                'self' => route('api.v1.sla-definitions.show', $this->id),
            ],
        ];
    }
}
