<?php

namespace App\Filament\Widgets;

use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;

abstract class BaseChartWidget extends ChartWidget
{
    /** @var list<string> */
    public const PALETTE = ['#5e81f4', '#3aa78d', '#e0b241', '#e26d5c', '#9d5ce5', '#3a86a7', '#d4699e', '#78909c', '#8bc34a', '#f97316'];

    protected function getOptions(): array|RawJs|null
    {
        return [
            'interaction' => ['mode' => 'index', 'intersect' => false],
            'plugins' => [
                'tooltip' => ['enabled' => true],
                'legend' => ['display' => true],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function doughnutOptions(): array
    {
        return [
            'interaction' => ['mode' => 'nearest', 'intersect' => true],
            'plugins' => [
                'tooltip' => ['enabled' => true],
                'legend' => ['display' => true, 'position' => 'bottom'],
            ],
        ];
    }

    /**
     * @param  list<\UnitEnum>  $cases
     * @return array{labels: list<string>, data: list<int>}
     */
    protected function countByEnum(Builder $query, string $column, array $cases): array
    {
        $counts = $query->toBase()
            ->selectRaw('`'.$column.'`, count(*) as total')
            ->groupBy($column)
            ->pluck('total', $column);

        return [
            'labels' => array_map(fn ($case): string => $case->getLabel(), $cases),
            'data' => array_map(fn ($case): int => (int) ($counts[$case->value] ?? 0), $cases),
        ];
    }
}
