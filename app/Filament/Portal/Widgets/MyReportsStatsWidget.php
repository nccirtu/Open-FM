<?php

namespace App\Filament\Portal\Widgets;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class MyReportsStatsWidget extends StatsOverviewWidget
{
    public static int $gridW = 12;

    public static int $gridH = 4;

    public static function canView(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $user = auth()->user();

        $mine = fn () => Ticket::query()->reportedBy($user);

        $open = $mine()->whereIn('status', TicketStatus::open())->count();
        $awaiting = $mine()->where('status', TicketStatus::Resolved)->count();
        $resolved = $mine()->whereNotNull('resolved_at')->where('resolved_at', '>=', now()->subDays(30))->count();
        $average = $this->averageResolutionTime($user);

        return [
            Stat::make(__('app.widgets.my_reports_open'), $open)
                ->color($open > 0 ? 'warning' : 'gray'),
            Stat::make(__('app.widgets.my_reports_awaiting'), $awaiting)
                ->description($awaiting > 0 ? __('portal.stats.awaiting_hint') : null)
                ->color($awaiting > 0 ? 'danger' : 'gray'),
            Stat::make(__('portal.stats.resolved_30d'), $resolved)
                ->color('success'),
            Stat::make(__('portal.stats.avg_duration'), $average ?? '—')
                ->description($average === null ? __('portal.stats.avg_duration_none') : null)
                ->color('info'),
        ];
    }

    protected function averageResolutionTime(User $user): ?string
    {
        $minutes = Ticket::query()
            ->reportedBy($user)
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '>=', now()->subDays(90))
            ->get(['created_at', 'resolved_at'])
            ->avg(fn (Ticket $ticket): float => abs($ticket->created_at->diffInMinutes($ticket->resolved_at)));

        if ($minutes === null) {
            return null;
        }

        return $minutes < 48 * 60
            ? __('portal.stats.hours', ['count' => Number::format(round($minutes / 60, 1), 1, locale: app()->getLocale())])
            : __('portal.stats.days', ['count' => Number::format(round($minutes / 1440, 1), 1, locale: app()->getLocale())]);
    }
}
