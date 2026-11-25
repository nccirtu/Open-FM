<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TicketType: string implements HasLabel
{
    case Fault = 'fault';
    case Defect = 'defect';
    case Request = 'request';

    public function getLabel(): string
    {
        return __("tickets.type.{$this->value}");
    }
}
