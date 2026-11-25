<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum WasteMissedReason: string implements HasLabel
{
    case DisposerAbsent = 'disposer_absent';
    case NotStaged = 'not_staged';
    case AccessBlocked = 'access_blocked';
    case Rejected = 'rejected';
    case Other = 'other';

    public function getLabel(): string
    {
        return __("waste.missed_reason.{$this->value}");
    }

    public function isDisposerFault(): bool
    {
        return $this === self::DisposerAbsent;
    }
}
