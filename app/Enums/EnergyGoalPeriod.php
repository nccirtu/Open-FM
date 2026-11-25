<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum EnergyGoalPeriod: string implements HasLabel
{
    case Year = 'year';
    case Month = 'month';

    public function getLabel(): string
    {
        return __("energy.goal_period.{$this->value}");
    }
}
