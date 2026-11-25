<?php

namespace App\Filament\Fm\Widgets;

use App\Enums\MaintenanceOrderStatus;
use App\Filament\Widgets\BaseChartWidget;
use App\Models\MaintenanceOrder;
use Carbon\CarbonImmutable;

class MaintenanceCompletedChartWidget extends BaseChartWidget
{
    public static int $gridW = 4;

    public static int $gridH = 8;

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', MaintenanceOrder::class) ?? false;
    }

    public function getHeading(): ?string
    {
        return __('app.widgets.maintenance_completed');
    }

    protected function getData(): array
    {
        $start = CarbonImmutable::today()->startOfMonth()->subMonths(5);

        $completed = MaintenanceOrder::query()
            ->where('status', MaintenanceOrderStatus::Completed)
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', $start)
            ->get(['completed_at'])
            ->groupBy(fn (MaintenanceOrder $order): string => $order->completed_at->format('Y-m'))
            ->map->count();

        $labels = [];
        $data = [];

        for ($month = $start; $month->lessThanOrEqualTo(CarbonImmutable::today()); $month = $month->addMonth()) {
            $labels[] = $month->translatedFormat('M y');
            $data[] = (int) ($completed[$month->format('Y-m')] ?? 0);
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => __('app.widgets.maintenance_completed_series'),
                    'data' => $data,
                    'backgroundColor' => self::PALETTE[1],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
