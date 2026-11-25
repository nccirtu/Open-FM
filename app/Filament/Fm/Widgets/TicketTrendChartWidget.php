<?php

namespace App\Filament\Fm\Widgets;

use App\Filament\Widgets\BaseChartWidget;
use App\Models\Ticket;
use App\Support\ServicePartner;
use Carbon\CarbonImmutable;

class TicketTrendChartWidget extends BaseChartWidget
{
    public static int $gridW = 8;

    public static int $gridH = 8;

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user !== null && ! ServicePartner::isExternal($user) && $user->can('viewAny', Ticket::class);
    }

    public function getHeading(): ?string
    {
        return __('app.widgets.ticket_trend');
    }

    protected function getData(): array
    {
        $start = CarbonImmutable::today()->startOfWeek()->subWeeks(11);

        $created = Ticket::query()->where('created_at', '>=', $start)->get(['created_at'])
            ->groupBy(fn (Ticket $ticket): string => $ticket->created_at->format('o-W'))
            ->map->count();

        $resolved = Ticket::query()->whereNotNull('resolved_at')->where('resolved_at', '>=', $start)->get(['resolved_at'])
            ->groupBy(fn (Ticket $ticket): string => $ticket->resolved_at->format('o-W'))
            ->map->count();

        $labels = [];
        $createdData = [];
        $resolvedData = [];

        for ($week = $start; $week->lessThanOrEqualTo(CarbonImmutable::today()); $week = $week->addWeek()) {
            $key = $week->format('o-W');
            $labels[] = 'KW'.$week->isoWeek;
            $createdData[] = (int) ($created[$key] ?? 0);
            $resolvedData[] = (int) ($resolved[$key] ?? 0);
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => __('app.widgets.ticket_trend_created'),
                    'data' => $createdData,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'transparent',
                ],
                [
                    'label' => __('app.widgets.ticket_trend_resolved'),
                    'data' => $resolvedData,
                    'borderColor' => '#22c55e',
                    'backgroundColor' => 'transparent',
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
