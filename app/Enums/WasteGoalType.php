<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum WasteGoalType: string implements HasLabel
{
    case MinRecyclingRate = 'min_recycling_rate';
    case MinSeparationRate = 'min_separation_rate';
    case MaxMass = 'max_mass';
    case Reduction = 'reduction';
    case MaxMassPerArea = 'max_mass_per_area';

    public function getLabel(): string
    {
        return __("waste.goal_type.{$this->value}");
    }

    public function unit(): string
    {
        return match ($this) {
            self::MinRecyclingRate, self::MinSeparationRate, self::Reduction => '%',
            self::MaxMass => 't',
            self::MaxMassPerArea => 'kg/m²',
        };
    }

    public function higherIsBetter(): bool
    {
        return in_array($this, [self::MinRecyclingRate, self::MinSeparationRate, self::Reduction], true);
    }
}
