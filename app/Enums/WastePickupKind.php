<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum WastePickupKind: string implements HasLabel
{
    case Regular = 'regular';
    case Extra = 'extra';
    case OnDemand = 'on_demand';

    public function getLabel(): string
    {
        return __("waste.pickup_kind.{$this->value}");
    }
}
