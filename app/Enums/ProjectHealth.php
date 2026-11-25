<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProjectHealth: string implements HasColor, HasLabel
{
    case Green = 'green';
    case Yellow = 'yellow';
    case Red = 'red';

    public function getLabel(): string
    {
        return __('projects.health.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Green => 'success',
            self::Yellow => 'warning',
            self::Red => 'danger',
        };
    }
}
