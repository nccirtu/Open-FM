<?php

namespace App\Filament\Fm\Widgets;

use App\Models\MaintenanceOrder;
use App\Models\MaintenancePlan;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MaintenanceStatsWidget extends StatsOverviewWidget
{
    public static int $gridW = 12;

    public static int $gridH = 3;

    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', MaintenanceOrder::class) ?? false;
    }

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $open = MaintenanceOrder::query()->open();

        return [
            Stat::make(__('maintenance.stats.overdue'), (clone $open)->whereDate('due_at', '<', now()->toDateString())->count())
                ->color('danger'),
            Stat::make(__('maintenance.stats.due_30d'), (clone $open)
                ->whereDate('due_at', '>=', now()->toDateString())
                ->whereDate('due_at', '<=', now()->addDays(30)->toDateString())
                ->count())
                ->color('warning'),
            Stat::make(__('maintenance.stats.open'), (clone $open)->count())
                ->color('info'),
            Stat::make(__('maintenance.stats.active_plans'), MaintenancePlan::query()->where('is_active', true)->count())
                ->color('success'),
        ];
    }
}
