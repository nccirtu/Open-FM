<?php

namespace App\Filament\Fm\Widgets;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Support\ServicePartner;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TicketStatsWidget extends StatsOverviewWidget
{
    public static int $gridW = 12;

    public static int $gridH = 3;

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user !== null && ! ServicePartner::isExternal($user) && $user->can('viewAny', Ticket::class);
    }

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $open = Ticket::query()->whereIn('status', TicketStatus::open());

        return [
            Stat::make(__('tickets.stats.open'), (clone $open)->count())
                ->color('warning'),
            Stat::make(__('tickets.stats.p1_open'), (clone $open)->where('priority', TicketPriority::P1)->count())
                ->color('danger'),
            Stat::make(__('tickets.stats.overdue'), (clone $open)->whereNotNull('due_at')->where('due_at', '<', now())->count())
                ->color('danger'),
            Stat::make(__('tickets.stats.resolved_30d'), Ticket::query()
                ->whereNotNull('resolved_at')
                ->where('resolved_at', '>=', now()->subDays(30))
                ->count())
                ->color('success'),
        ];
    }
}
