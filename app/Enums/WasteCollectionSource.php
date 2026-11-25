<?php

namespace App\Enums;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum WasteCollectionSource: string implements HasIcon, HasLabel
{
    case Scan = 'scan';
    case Manual = 'manual';
    case Import = 'import';
    case Api = 'api';
    case Sensor = 'sensor';
    case Schedule = 'schedule';

    public function getLabel(): string
    {
        return __("waste.collection_source.{$this->value}");
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Scan => 'heroicon-o-qr-code',
            self::Manual => 'heroicon-o-pencil-square',
            self::Import => 'heroicon-o-arrow-up-tray',
            self::Api => 'heroicon-o-cpu-chip',
            self::Sensor => 'heroicon-o-signal',
            self::Schedule => 'heroicon-o-clock',
        };
    }
}
