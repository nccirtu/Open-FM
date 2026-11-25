<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum EnergyGoalType: string implements HasLabel
{
    case MaxConsumption = 'max_consumption';
    case MaxIntensity = 'max_intensity';
    case Reduction = 'reduction';

    public function getLabel(): string
    {
        return __("energy.goal_type.{$this->value}");
    }
}
