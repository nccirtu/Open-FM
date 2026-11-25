<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum WasteMassSource: string implements HasColor, HasLabel
{
    case Measured = 'measured';
    case Estimated = 'estimated';

    public function getLabel(): string
    {
        return __("waste.mass_source.{$this->value}");
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Measured => 'success',
            self::Estimated => 'info',
        };
    }
}
