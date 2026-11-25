<?php

namespace App\Enums;

use Carbon\CarbonInterface;
use Filament\Support\Contracts\HasLabel;

enum MaintenanceIntervalUnit: string implements HasLabel
{
    case Days = 'days';
    case Weeks = 'weeks';
    case Months = 'months';
    case Years = 'years';

    public function getLabel(): string
    {
        return __("maintenance.interval_unit.{$this->value}");
    }

    public function addTo(CarbonInterface $date, int $value): CarbonInterface
    {
        return match ($this) {
            self::Days => $date->addDays($value),
            self::Weeks => $date->addWeeks($value),
            self::Months => $date->addMonths($value),
            self::Years => $date->addYears($value),
        };
    }
}
