<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum StructureNodeType: string implements HasColor, HasIcon, HasLabel
{
    case Building = 'building';
    case Floor = 'floor';
    case Unit = 'unit';
    case Room = 'room';
    case Zone = 'zone';
    case Outdoor = 'outdoor';

    public function getLabel(): string
    {
        return __("property.structure.types.{$this->value}");
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Building => 'primary',
            self::Floor => 'info',
            self::Unit => 'warning',
            self::Room => 'success',
            self::Zone => 'gray',
            self::Outdoor => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Building => 'heroicon-o-building-office-2',
            self::Floor => 'heroicon-o-square-3-stack-3d',
            self::Unit => 'heroicon-o-home',
            self::Room => 'heroicon-o-cube-transparent',
            self::Zone => 'heroicon-o-squares-2x2',
            self::Outdoor => 'heroicon-o-sun',
        };
    }
}
