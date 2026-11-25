<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum MeterKind: string implements HasColor, HasLabel
{
    case Main = 'main';
    case Sub = 'sub';
    case Virtual = 'virtual';

    public function getLabel(): string
    {
        return __("energy.kind.{$this->value}");
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Main => 'primary',
            self::Sub => 'gray',
            self::Virtual => 'info',
        };
    }
}
