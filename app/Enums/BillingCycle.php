<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum BillingCycle: string implements HasLabel
{
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Yearly = 'yearly';
    case OneTime = 'one_time';

    public function getLabel(): string
    {
        return __("contracts.billing_cycle.{$this->value}");
    }
}
