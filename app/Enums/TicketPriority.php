<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TicketPriority: string implements HasColor, HasLabel
{
    case P1 = 'p1';
    case P2 = 'p2';
    case P3 = 'p3';

    public function getLabel(): string
    {
        return __("tickets.priority.{$this->value}");
    }

    public function getColor(): string
    {
        return match ($this) {
            self::P1 => 'danger',
            self::P2 => 'warning',
            self::P3 => 'gray',
        };
    }
}
