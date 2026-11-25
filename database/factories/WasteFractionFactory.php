<?php

namespace Database\Factories;

use App\Models\WasteFraction;
use App\Models\WasteFractionGroup;
use App\Models\WasteTreatment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WasteFraction>
 */
class WasteFractionFactory extends Factory
{
    protected $model = WasteFraction::class;

    public function definition(): array
    {
        return [
            'name' => 'Papier/Pappe',
            'code' => '20 01 01',
            'is_hazardous' => false,
            'color' => '#2f68cc',
            'density_kg_m3' => 60,
            'is_active' => true,
        ];
    }

    public function group(string $key): static
    {
        return $this->state(fn (): array => ['waste_fraction_group_id' => WasteFractionGroup::query()->where('key', $key)->value('id')]);
    }

    public function treatment(string $code): static
    {
        return $this->state(fn (): array => ['waste_treatment_id' => WasteTreatment::query()->where('code', $code)->value('id')]);
    }

    public function paper(): static
    {
        return $this->group('paper')->treatment('R3');
    }

    public function mixed(): static
    {
        return $this->state([
            'name' => 'Gemischte Siedlungsabfälle',
            'code' => '20 03 01',
            'color' => '#4d5459',
            'density_kg_m3' => 120,
        ])->group('mixed')->treatment('R1');
    }

    public function bio(): static
    {
        return $this->state([
            'name' => 'Bioabfall',
            'code' => '20 01 08',
            'color' => '#8a5a2e',
            'density_kg_m3' => 450,
        ])->group('bio')->treatment('R3');
    }

    public function hazardous(): static
    {
        return $this->state([
            'name' => 'Leuchtstoffröhren',
            'code' => '20 01 21*',
            'is_hazardous' => true,
            'color' => '#d1472a',
            'density_kg_m3' => 150,
        ])->group('hazardous')->treatment('R4');
    }
}
