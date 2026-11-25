<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CostEntryKind: string implements HasColor, HasLabel
{
    case Actual = 'actual';
    case Estimate = 'estimate';

    public function getLabel(): string
    {
        return __('projects.cost_kind.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Actual => 'info',
            self::Estimate => 'gray',
        };
    }
}
