<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ContractStatus: string implements HasColor, HasLabel
{
    case Active = 'active';
    case Terminated = 'terminated';
    case Expired = 'expired';

    public function getLabel(): string
    {
        return __("contracts.status.{$this->value}");
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Terminated => 'gray',
            self::Expired => 'danger',
        };
    }
}
