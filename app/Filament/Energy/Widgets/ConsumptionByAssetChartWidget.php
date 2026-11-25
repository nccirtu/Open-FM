<?php

namespace App\Filament\Energy\Widgets;

use App\Enums\EnergyMedium;
use App\Filament\Energy\Concerns\InteractsWithEnergyFilters;
use App\Filament\Widgets\BaseChartWidget;
use App\Models\Meter;
use App\Services\Energy\EnergyReport;
use Filament\Support\RawJs;

class ConsumptionByAssetChartWidget extends BaseChartWidget
{
    use InteractsWithEnergyFilters;

    public static int $gridW = 6;

    public static int $gridH = 8;

    public ?string $filter = 'electricity';

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Meter::class) ?? false;
    }

    public function getHeading(): ?string
    {
        return __('energy.charts.by_asset');
    }

    public function getDescription(): ?string
    {
        return $this->filters()->label();
    }

    protected function getFilters(): ?array
    {
        return collect(EnergyMedium::cases())->mapWithKeys(fn (EnergyMedium $medium): array => [$medium->value => $medium->getLabel()])->all();
    }

    protected function getData(): array
    {
        $medium = EnergyMedium::tryFrom((string) $this->filter) ?? EnergyMedium::Electricity;
        $filters = $this->filters();
        $meters = $filters->scope(Meter::query())->where('medium', $medium->value)->has('measuredAssets')->with('measuredAssets')->get();
        $rows = app(EnergyReport::class)->perAsset($meters, $filters->from, $filters->to)->take(10);

        return [
            'labels' => $rows->map(fn (array $row): string => $row['asset']->name)->all(),
            'datasets' => [
                [
                    'label' => __('energy.charts.consumption_in', ['unit' => $medium->evaluationUnit()]),
                    'data' => $rows->map(fn (array $row): float => round($row['consumption'], 1))->all(),
                    'backgroundColor' => $medium->chartColor(),
                    'borderRadius' => 4,
                ],
            ],
        ];
    }

    protected function getOptions(): array|RawJs|null
    {
        return [
            ...parent::getOptions(),
            'indexAxis' => 'y',
            'plugins' => ['legend' => ['display' => false], 'tooltip' => ['enabled' => true]],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
