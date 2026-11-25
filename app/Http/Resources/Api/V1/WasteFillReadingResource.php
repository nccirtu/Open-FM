<?php

namespace App\Http\Resources\Api\V1;

use App\Models\WasteFillReading;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WasteFillReading
 */
class WasteFillReadingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'waste_container_id' => $this->waste_container_id,
            'measured_at' => $this->measured_at?->toIso8601String(),
            'fill_level_pct' => $this->fill_level_pct,
            'temperature_c' => $this->temperature_c !== null ? (float) $this->temperature_c : null,
            'sensor_id' => $this->sensor_id,
            'pickup_detected' => $this->pickup_detected,
        ];
    }
}
