<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AssetServiceStatus: string implements HasColor, HasLabel
{
    case InOperation = 'in_operation';
    case OutOfOperation = 'out_of_operation';
    case InStorage = 'in_storage';
    case Disposed = 'disposed';

    public function getLabel(): string
    {
        return __("assets.service_status.{$this->value}");
    }

    public function getColor(): string
    {
        return match ($this) {
            self::InOperation => 'success',
            self::OutOfOperation => 'danger',
            self::InStorage => 'warning',
            self::Disposed => 'gray',
        };
    }
}
