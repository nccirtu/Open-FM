<?php

namespace App\Filament\Fm\Widgets;

use App\Filament\Widgets\BaseChartWidget;
use App\Models\Asset;
use App\Models\AssetType;
use Filament\Support\RawJs;

class AssetsByTypeChartWidget extends BaseChartWidget
{
    public static int $gridW = 4;

    public static int $gridH = 8;

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Asset::class) ?? false;
    }

    public function getHeading(): ?string
    {
        return __('app.widgets.assets_by_type');
    }

    protected function getData(): array
    {
        $top = AssetType::query()
            ->withCount('assets')
            ->orderByDesc('assets_count')
            ->limit(10)
            ->get()
            ->filter(fn (AssetType $type): bool => $type->assets_count > 0);

        return [
            'labels' => $top->pluck('name')->all(),
            'datasets' => [
                [
                    'label' => 'Assets',
                    'data' => $top->pluck('assets_count')->all(),
                    'backgroundColor' => self::PALETTE[0],
                ],
            ],
        ];
    }

    protected function getOptions(): array|RawJs|null
    {
        return [
            ...parent::getOptions(),
            'indexAxis' => 'y',
            'plugins' => ['tooltip' => ['enabled' => true], 'legend' => ['display' => false]],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
