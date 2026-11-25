<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum EnergyMedium: string implements HasColor, HasIcon, HasLabel
{
    case Electricity = 'electricity';
    case Gas = 'gas';
    case Heat = 'heat';
    case Cold = 'cold';
    case Water = 'water';
    case Oil = 'oil';
    case PvGeneration = 'pv_generation';

    public function getLabel(): string
    {
        return __("energy.medium.{$this->value}");
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Electricity => 'warning',
            self::Gas => 'success',
            self::Heat => 'danger',
            self::Cold => 'info',
            self::Water => 'info',
            self::Oil => 'gray',
            self::PvGeneration => 'success',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Electricity => 'heroicon-o-bolt',
            self::Gas => 'heroicon-o-fire',
            self::Heat => 'heroicon-o-sun',
            self::Cold => 'heroicon-o-cloud',
            self::Water => 'heroicon-o-beaker',
            self::Oil => 'heroicon-o-circle-stack',
            self::PvGeneration => 'heroicon-o-sparkles',
        };
    }

    public function chartColor(): string
    {
        return match ($this) {
            self::Electricity => '#d39b00',
            self::Gas => '#0f9d76',
            self::Heat => '#d4501f',
            self::Cold => '#1aa3b8',
            self::Water => '#2b7fd4',
            self::Oil => '#78716c',
            self::PvGeneration => '#6aa514',
        };
    }

    public function isEnergy(): bool
    {
        return $this !== self::Water;
    }

    /**
     * @return array<int, string>
     */
    public static function energyValues(): array
    {
        return array_values(array_map(
            fn (self $medium): string => $medium->value,
            array_filter(self::cases(), fn (self $medium): bool => $medium->isEnergy() && $medium !== self::PvGeneration),
        ));
    }

    public function defaultUnit(): string
    {
        return match ($this) {
            self::Gas, self::Water => 'm³',
            self::Oil => 'l',
            default => 'kWh',
        };
    }

    public function evaluationUnit(): string
    {
        return $this->isEnergy() ? 'kWh' : 'm³';
    }
}
