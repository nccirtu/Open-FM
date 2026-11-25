<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TicketCategory: string implements HasLabel
{
    case Electrical = 'electrical';
    case Plumbing = 'plumbing';
    case Hvac = 'hvac';
    case Refrigeration = 'refrigeration';
    case Building = 'building';
    case Safety = 'safety';
    case Cleaning = 'cleaning';
    case Other = 'other';

    public function getLabel(): string
    {
        return __("tickets.category.{$this->value}");
    }
}
