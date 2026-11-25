<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum WasteIntervalUnit: string implements HasLabel
{
    case Week = 'week';
    case Month = 'month';
    case Year = 'year';
    case Once = 'once';

    public function getLabel(): string
    {
        return __("waste.interval_unit.{$this->value}");
    }

    public function hasInterval(): bool
    {
        return $this !== self::Once;
    }
}
