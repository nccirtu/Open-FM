<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum MaintenanceOrderStatus: string implements HasColor, HasLabel
{
    case Open = 'open';
    case Completed = 'completed';
    case Skipped = 'skipped';

    public function getLabel(): string
    {
        return __("maintenance.order_status.{$this->value}");
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Open => 'warning',
            self::Completed => 'success',
            self::Skipped => 'gray',
        };
    }
}
