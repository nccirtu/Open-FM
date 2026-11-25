<?php

namespace App\Filament\Fm\Widgets;

use App\Enums\AssetServiceStatus;
use App\Filament\Widgets\BaseChartWidget;
use App\Models\Asset;
use Filament\Support\RawJs;

class AssetsByServiceStatusChartWidget extends BaseChartWidget
{
    public static int $gridW = 4;

    public static int $gridH = 8;

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Asset::class) ?? false;
    }

    public function getHeading(): ?string
    {
        return __('app.widgets.assets_by_service_status');
    }

    protected function getData(): array
    {
        $counts = $this->countByEnum(
            Asset::query()->whereNotNull('service_status'),
            'service_status',
            AssetServiceStatus::cases(),
        );

        return [
            'labels' => $counts['labels'],
            'datasets' => [
                [
                    'data' => $counts['data'],
                    'backgroundColor' => ['#22c55e', '#ef4444', '#e0b241', '#78909c'],
                ],
            ],
        ];
    }

    protected function getOptions(): array|RawJs|null
    {
        return $this->doughnutOptions();
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
