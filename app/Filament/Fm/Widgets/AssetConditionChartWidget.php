<?php

namespace App\Filament\Fm\Widgets;

use App\Enums\AssetCondition;
use App\Filament\Widgets\BaseChartWidget;
use App\Models\Asset;
use Filament\Support\RawJs;

class AssetConditionChartWidget extends BaseChartWidget
{
    public static int $gridW = 4;

    public static int $gridH = 8;

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Asset::class) ?? false;
    }

    public function getHeading(): ?string
    {
        return __('app.widgets.asset_condition');
    }

    protected function getData(): array
    {
        $counts = Asset::query()
            ->selectRaw('`condition`, count(*) as total')
            ->whereNotNull('condition')
            ->groupBy('condition')
            ->pluck('total', 'condition');

        $palette = [
            AssetCondition::Good->value => '#22c55e',
            AssetCondition::Fair->value => '#eab308',
            AssetCondition::Poor->value => '#ef4444',
        ];

        $labels = [];
        $data = [];
        $colors = [];

        foreach (AssetCondition::cases() as $condition) {
            $labels[] = $condition->getLabel();
            $data[] = (int) ($counts[$condition->value] ?? 0);
            $colors[] = $palette[$condition->value] ?? '#a1a1aa';
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $colors,
                ],
            ],
        ];
    }

    protected function getOptions(): RawJs|array|null
    {
        return $this->doughnutOptions();
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
