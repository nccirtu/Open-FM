<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum WastePickupStatus: string implements HasColor, HasIcon, HasLabel
{
    case Requested = 'requested';
    case Planned = 'planned';
    case Confirmed = 'confirmed';
    case Missed = 'missed';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return __("waste.pickup_status.{$this->value}");
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Requested => 'info',
            self::Planned => 'gray',
            self::Confirmed => 'success',
            self::Missed => 'danger',
            self::Cancelled => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Requested => 'heroicon-o-hand-raised',
            self::Planned => 'heroicon-o-calendar',
            self::Confirmed => 'heroicon-o-check-circle',
            self::Missed => 'heroicon-o-x-circle',
            self::Cancelled => 'heroicon-o-minus-circle',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Requested, self::Planned], true);
    }
}
