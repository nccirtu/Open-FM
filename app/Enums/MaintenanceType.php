<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum MaintenanceType: string implements HasLabel
{
    case Maintenance = 'maintenance';
    case Inspection = 'inspection';

    public function getLabel(): string
    {
        return __("maintenance.type.{$this->value}");
    }
}
