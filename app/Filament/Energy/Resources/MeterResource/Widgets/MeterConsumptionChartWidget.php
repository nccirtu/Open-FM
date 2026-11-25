<?php

namespace App\Filament\Energy\Resources\MeterResource\Widgets;

use App\Filament\Widgets\BaseChartWidget;
use App\Models\Meter;
use App\Services\Energy\EnergyReport;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

class MeterConsumptionChartWidget extends BaseChartWidget
{
    public ?Model $record = null;

    public ?string $filter = 'days';

    protected ?string $maxHeight = '260px';

    public function getHeading(): ?string
    {
        return __('energy.charts.meter_consumption');
    }

    protected function getFilters(): ?array
    {
        return [
            'days' => __('energy.charts.last_30_days'),
            'months' => __('energy.charts.last_12_months'),
        ];
    }

    protected function getData(): array
    {
        if (! $this->record instanceof Meter) {
            return ['labels' => [], 'datasets' => []];
        }

        $report = app(EnergyReport::class);
        $today = CarbonImmutable::today();
        $color = $this->record->medium?->chartColor() ?? self::PALETTE[0];
        $unit = $this->record->evaluationUnit();

        if ($this->filter === 'months') {
            $start = $today->startOfMonth()->subMonths(11);
            $current = $report->monthlyTotals([$this->record], $start, 12);
            $previous = $report->monthlyTotals([$this->record], $start->subYear(), 12);

            return [
                'labels' => array_map(fn (string $month): string => CarbonImmutable::parse($month.'-01')->translatedFormat('M y'), array_keys($current)),
                'datasets' => [
                    ['label' => __('energy.charts.consumption_in', ['unit' => $unit]), 'data' => array_map(fn (float $v): float => round($v, 1), array_values($current)), 'backgroundColor' => $color, 'borderRadius' => 4],
                    ['label' => __('energy.charts.previous_year'), 'data' => array_map(fn (float $v): float => round($v, 1), array_values($previous)), 'backgroundColor' => '#94a3b8', 'borderRadius' => 4],
                ],
            ];
        }

        $series = $report->dailySeries($this->record, $today->subDays(29), $today);

        return [
            'labels' => array_map(fn (string $day): string => CarbonImmutable::parse($day)->format('d.m.'), array_keys($series)),
            'datasets' => [
                ['label' => __('energy.charts.consumption_in', ['unit' => $unit]), 'data' => array_map(fn (?float $v): ?float => $v === null ? null : round($v, 1), array_values($series)), 'backgroundColor' => $color, 'borderRadius' => 4],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
