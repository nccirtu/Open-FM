<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProjectStatus: string implements HasColor, HasLabel
{
    case Open = 'open';
    case Active = 'active';
    case OnHold = 'on_hold';
    case Done = 'done';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return __('projects.status.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Open => 'gray',
            self::Active => 'info',
            self::OnHold => 'warning',
            self::Done => 'success',
            self::Cancelled => 'danger',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Open, self::Active, self::OnHold], true);
    }

    /**
     * @return array<int, self>
     */
    public static function openCases(): array
    {
        return [self::Open, self::Active, self::OnHold];
    }
}
