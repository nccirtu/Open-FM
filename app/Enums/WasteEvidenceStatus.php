<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum WasteEvidenceStatus: string implements HasColor, HasIcon, HasLabel
{
    case Open = 'open';
    case Available = 'available';
    case NotRequired = 'not_required';

    public function getLabel(): string
    {
        return __("waste.evidence_status.{$this->value}");
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Open => 'danger',
            self::Available => 'success',
            self::NotRequired => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Open => 'heroicon-o-x-circle',
            self::Available => 'heroicon-o-check-circle',
            self::NotRequired => 'heroicon-o-minus-circle',
        };
    }
}
