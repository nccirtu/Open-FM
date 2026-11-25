<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TicketStatus: string implements HasColor, HasLabel
{
    case Reported = 'reported';
    case Accepted = 'accepted';
    case InProgress = 'in_progress';
    case Waiting = 'waiting';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function getLabel(): string
    {
        return __("tickets.status.{$this->value}");
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Reported => 'warning',
            self::Accepted => 'info',
            self::InProgress => 'primary',
            self::Waiting => 'warning',
            self::Resolved => 'success',
            self::Closed => 'gray',
        };
    }

    /**
     * @return list<self>
     */
    public static function open(): array
    {
        return [self::Reported, self::Accepted, self::InProgress, self::Waiting];
    }
}
