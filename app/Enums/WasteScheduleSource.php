<?php

namespace App\Enums;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum WasteScheduleSource: string implements HasIcon, HasLabel
{
    case Rule = 'rule';
    case Ics = 'ics';
    case OnDemand = 'on_demand';

    public function getLabel(): string
    {
        return __("waste.schedule_source.{$this->value}");
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Rule => 'heroicon-o-arrow-path',
            self::Ics => 'heroicon-o-calendar-days',
            self::OnDemand => 'heroicon-o-hand-raised',
        };
    }
}
