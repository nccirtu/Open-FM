<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TicketSuspensionReason: string implements HasLabel
{
    case AwaitingParts = 'awaiting_parts';
    case AwaitingPartner = 'awaiting_partner';
    case Other = 'other';

    public function getLabel(): string
    {
        return __("tickets.suspension_reason.{$this->value}");
    }
}
