<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum MeterReadingStatus: string implements HasColor, HasLabel
{
    case Plausible = 'plausible';
    case Suspicious = 'suspicious';

    public function getLabel(): string
    {
        return __("energy.status.{$this->value}");
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Plausible => 'success',
            self::Suspicious => 'danger',
        };
    }
}
