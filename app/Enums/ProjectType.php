<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ProjectType: string implements HasLabel
{
    case NewBuild = 'new_build';
    case Remodel = 'remodel';
    case Renovation = 'renovation';
    case Maintenance = 'maintenance';
    case Relocation = 'relocation';
    case Other = 'other';

    public function getLabel(): string
    {
        return __('projects.type.'.$this->value);
    }
}
