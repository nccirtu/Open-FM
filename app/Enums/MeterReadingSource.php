<?php

namespace App\Enums;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum MeterReadingSource: string implements HasIcon, HasLabel
{
    case Manual = 'manual';
    case Import = 'import';
    case Api = 'api';

    public function getLabel(): string
    {
        return __("energy.source.{$this->value}");
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Manual => 'heroicon-o-device-phone-mobile',
            self::Import => 'heroicon-o-arrow-up-tray',
            self::Api => 'heroicon-o-cpu-chip',
        };
    }
}
