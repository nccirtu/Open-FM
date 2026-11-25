<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CompanyRole: string implements HasColor, HasLabel
{
    case Supplier = 'supplier';
    case Client = 'client';
    case Both = 'both';

    public function getLabel(): string
    {
        return __('companies.role.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Supplier => 'info',
            self::Client => 'success',
            self::Both => 'primary',
        };
    }

    public function isSupplier(): bool
    {
        return $this !== self::Client;
    }

    public function isClient(): bool
    {
        return $this !== self::Supplier;
    }
}
