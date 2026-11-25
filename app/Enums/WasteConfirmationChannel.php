<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum WasteConfirmationChannel: string implements HasLabel
{
    case Scan = 'scan';
    case Data = 'data';
    case Sensor = 'sensor';

    public function getLabel(): string
    {
        return __("waste.confirmation_channel.{$this->value}");
    }
}
