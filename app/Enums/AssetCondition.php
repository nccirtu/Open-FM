<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AssetCondition: string implements HasColor, HasLabel
{
    case Good = 'good';
    case Fair = 'fair';
    case Poor = 'poor';

    public function getLabel(): string
    {
        return __("assets.condition.{$this->value}");
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Good => 'success',
            self::Fair => 'warning',
            self::Poor => 'danger',
        };
    }
}
