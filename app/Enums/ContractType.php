<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ContractType: string implements HasLabel
{
    case Maintenance = 'maintenance';
    case Service = 'service';
    case Rental = 'rental';
    case Insurance = 'insurance';
    case Utility = 'utility';
    case WasteDisposal = 'waste_disposal';
    case Other = 'other';

    public function getLabel(): string
    {
        return __("contracts.type.{$this->value}");
    }
}
