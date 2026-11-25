<?php

namespace App\Filament\Waste\Widgets;

use App\Filament\Waste\Concerns\InteractsWithWasteFilters;
use App\Filament\Widgets\BaseChartWidget;
use App\Models\WasteCollection;
use App\Services\Waste\WasteReport;

class WasteByFractionChartWidget extends BaseChartWidget
{
    use InteractsWithWasteFilters;

    protected static ?int $sort = 2;

    public static int $gridW = 7;

    public static int $gridH = 9;

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', WasteCollection::class) ?? false;
    }

    public function getHeading(): ?string
    {
        return __('waste.charts.mass_by_fraction');
    }

    public function getDescription(): ?string
    {
        return $this->filters()->label();
    }

    protected function getData(): array
    {
        $series = app(WasteReport::class)->massByFractionOverTime($this->filters());

        return [
            'labels' => $series['labels'],
            'datasets' => array_map(fn (array $dataset): array => [
                'label' => $dataset['label'],
                'data' => $dataset['data'],
                'backgroundColor' => $dataset['color'],
                'borderColor' => $dataset['color'],
                'stack' => 'mass',
            ], $series['datasets']),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'x' => ['stacked' => true],
                'y' => ['stacked' => true, 'title' => ['display' => true, 'text' => 't']],
            ],
            'plugins' => ['legend' => ['position' => 'bottom']],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
