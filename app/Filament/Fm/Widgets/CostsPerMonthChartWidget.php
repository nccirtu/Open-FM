<?php

namespace App\Filament\Fm\Widgets;

use App\Enums\CostEntryKind;
use App\Filament\Widgets\BaseChartWidget;
use App\Models\CostEntry;
use App\Models\Project;
use Carbon\CarbonImmutable;

class CostsPerMonthChartWidget extends BaseChartWidget
{
    public static int $gridW = 8;

    public static int $gridH = 8;

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Project::class) ?? false;
    }

    public function getHeading(): ?string
    {
        return __('app.widgets.costs_per_month');
    }

    protected function getData(): array
    {
        $start = CarbonImmutable::today()->startOfMonth()->subMonths(11);

        $sums = CostEntry::query()
            ->where('kind', CostEntryKind::Actual)
            ->get(['amount', 'booked_on', 'created_at'])
            ->groupBy(fn (CostEntry $entry): string => ($entry->booked_on ?? $entry->created_at)->format('Y-m'))
            ->map(fn ($entries): float => (float) $entries->sum('amount'));

        $labels = [];
        $data = [];

        for ($month = $start; $month->lessThanOrEqualTo(CarbonImmutable::today()); $month = $month->addMonth()) {
            $labels[] = $month->translatedFormat('M y');
            $data[] = round($sums[$month->format('Y-m')] ?? 0, 2);
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => __('app.widgets.costs_per_month_series'),
                    'data' => $data,
                    'borderColor' => self::PALETTE[0],
                    'backgroundColor' => 'rgba(94, 129, 244, 0.15)',
                    'fill' => true,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
