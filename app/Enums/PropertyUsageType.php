<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PropertyUsageType: string implements HasColor, HasLabel
{
    case Commercial = 'commercial';
    case Residential = 'residential';
    case Mixed = 'mixed';

    public function getLabel(): string
    {
        return __("property.usage_type.{$this->value}");
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Commercial => 'info',
            self::Residential => 'success',
            self::Mixed => 'warning',
        };
    }
}
