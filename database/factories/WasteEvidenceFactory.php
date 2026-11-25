<?php

namespace Database\Factories;

use App\Enums\WasteEvidenceStatus;
use App\Models\Property;
use App\Models\WasteEvidence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WasteEvidence>
 */
class WasteEvidenceFactory extends Factory
{
    protected $model = WasteEvidence::class;

    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'year' => (int) now()->year,
            'type' => 'acceptor_declaration',
            'status' => WasteEvidenceStatus::Open,
        ];
    }
}
