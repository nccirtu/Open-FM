<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum WasteEsrsCategory: string implements HasLabel
{
    case Reuse = 'reuse';
    case Recycling = 'recycling';
    case OtherRecovery = 'other_recovery';
    case Incineration = 'incineration';
    case Landfill = 'landfill';
    case OtherDisposal = 'other_disposal';

    public function getLabel(): string
    {
        return __("waste.esrs_category.{$this->value}");
    }

    public function isDiverted(): bool
    {
        return in_array($this, [self::Reuse, self::Recycling, self::OtherRecovery], true);
    }
}
