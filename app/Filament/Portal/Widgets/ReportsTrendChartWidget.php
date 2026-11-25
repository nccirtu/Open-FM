<?php

namespace App\Filament\Portal\Widgets;

use App\Filament\Widgets\BaseChartWidget;
use App\Models\Ticket;
use Carbon\CarbonImmutable;
use Filament\Support\RawJs;

class ReportsTrendChartWidget extends BaseChartWidget
{
    public static int $gridW = 4;

    public static int $gridH = 10;

    public static function canView(): bool
    {
        return auth()->check();
    }

    public function getHeading(): ?string
    {
        return __('portal.widgets.trend');
    }

    protected function getData(): array
    {
        $user = auth()->user();
        $start = CarbonImmutable::today()->startOfWeek()->subWeeks(11);

        $reported = Ticket::query()->reportedBy($user)->where('created_at', '>=', $start)->get(['created_at'])
            ->groupBy(fn (Ticket $ticket): string => $ticket->created_at->format('o-W'))
            ->map->count();

        $resolved = Ticket::query()->reportedBy($user)->whereNotNull('resolved_at')->where('resolved_at', '>=', $start)->get(['resolved_at'])
            ->groupBy(fn (Ticket $ticket): string => $ticket->resolved_at->format('o-W'))
            ->map->count();

        $labels = [];
        $reportedData = [];
        $resolvedData = [];

        for ($week = $start; $week->lessThanOrEqualTo(CarbonImmutable::today()); $week = $week->addWeek()) {
            $key = $week->format('o-W');
            $labels[] = 'KW'.$week->isoWeek;
            $reportedData[] = (int) ($reported[$key] ?? 0);
            $resolvedData[] = (int) ($resolved[$key] ?? 0);
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => __('portal.widgets.trend_reported'),
                    'data' => $reportedData,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'transparent',
                ],
                [
                    'label' => __('portal.widgets.trend_resolved'),
                    'data' => $resolvedData,
                    'borderColor' => '#22c55e',
                    'backgroundColor' => 'transparent',
                ],
            ],
        ];
    }

    protected function getOptions(): array|RawJs|null
    {
        return [
            ...parent::getOptions(),
            'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
