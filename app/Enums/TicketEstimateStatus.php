<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TicketEstimateStatus: string implements HasColor, HasLabel
{
    case Requested = 'requested';
    case Received = 'received';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function getLabel(): string
    {
        return __("tickets.estimate_status.{$this->value}");
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Requested => 'warning',
            self::Received => 'info',
            self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }
}
