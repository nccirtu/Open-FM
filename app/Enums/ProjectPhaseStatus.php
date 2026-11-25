<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProjectPhaseStatus: string implements HasColor, HasLabel
{
    case Planned = 'planned';
    case Active = 'active';
    case Done = 'done';

    public function getLabel(): string
    {
        return __('projects.phase_status.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Planned => 'gray',
            self::Active => 'info',
            self::Done => 'success',
        };
    }
}
