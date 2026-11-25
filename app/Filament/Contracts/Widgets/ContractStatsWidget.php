<?php

namespace App\Filament\Contracts\Widgets;

use App\Models\Contract;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class ContractStatsWidget extends StatsOverviewWidget
{
    public static int $gridW = 12;

    public static int $gridH = 3;

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Contract::class) ?? false;
    }

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $active = Contract::query()->active()->get();

        $needsDecision = $active->filter(fn (Contract $contract): bool => $contract->needsTerminationReminder());

        $yearlyCosts = $active->sum(fn (Contract $contract): float => $contract->yearlyAmount());

        return [
            Stat::make(__('contracts.stats.active'), $active->count())
                ->color('success'),
            Stat::make(__('contracts.stats.needs_decision'), $needsDecision->count())
                ->color($needsDecision->isEmpty() ? 'success' : 'danger'),
            Stat::make(__('contracts.stats.yearly_costs'), Number::currency($yearlyCosts, 'EUR', app()->getLocale()))
                ->color('info'),
        ];
    }
}
