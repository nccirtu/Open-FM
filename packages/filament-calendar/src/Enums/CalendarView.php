<?php

namespace OpenFm\FilamentCalendar\Enums;

use Filament\Support\Contracts\HasLabel;

enum CalendarView: string implements HasLabel
{
    case Month = 'month';
    case Week = 'week';
    case List = 'list';

    public function getLabel(): string
    {
        return __("filament-calendar::calendar.views.{$this->value}");
    }
}
