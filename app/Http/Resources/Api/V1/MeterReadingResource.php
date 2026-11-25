<?php

namespace App\Http\Resources\Api\V1;

use App\Models\MeterReading;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MeterReading
 */
class MeterReadingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'meter_id' => $this->meter_id,
            'read_at' => $this->read_at?->toIso8601String(),
            'value' => $this->value,
            'source' => $this->source?->value,
            'status' => $this->status?->value,
            'flag' => $this->flag,
            'note' => $this->note,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
