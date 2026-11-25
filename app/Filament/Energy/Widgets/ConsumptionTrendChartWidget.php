<?php

namespace App\Filament\Energy\Widgets;

use App\Enums\EnergyMedium;
use App\Filament\Energy\Concerns\InteractsWithEnergyFilters;
use App\Filament\Widgets\BaseChartWidget;
use App\Models\Meter;
use App\Services\Energy\EnergyReport;
use Carbon\CarbonImmutable;

class ConsumptionTrendChartWidget extends BaseChartWidget
{
    use InteractsWithEnergyFilters;

    public static int $gridW = 8;

    public static int $gridH = 8;

    public ?string $filter = 'electricity';

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Meter::class) ?? false;
    }

    public function getHeading(): ?string
    {
        return $this->filters()->usesMonths() ? __('energy.charts.consumption_trend') : __('energy.charts.consumption_per_day');
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
        $report = app(EnergyReport::class);
        $filters = $this->filters();
        [$previousFrom, $previousTo] = $filters->previousYear();
        $meters = fn () => $filters->scope($report->topLevelMeters($medium));

        if ($filters->usesMonths()) {
            $months = (int) $filters->from->startOfMonth()->diffInMonths($filters->to->startOfMonth()) + 1;
            $current = $report->monthlyTotals($meters(), $filters->from->startOfMonth(), $months);
            $previous = array_values($report->monthlyTotals($meters(), $filters->from->startOfMonth()->subYear(), $months));
            $labels = array_map(fn (string $month): string => CarbonImmutable::parse($month.'-01')->translatedFormat('M y'), array_keys($current));
        } else {
            $current = $report->dailyTotals($meters(), $filters->from, $filters->to);
            $previous = array_values($report->dailyTotals($meters(), $previousFrom, $previousFrom->addDays(count($current) - 1)));
            $labels = array_map(fn (string $day): string => CarbonImmutable::parse($day)->format('d.m.'), array_keys($current));
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => __('energy.charts.consumption_in', ['unit' => $medium->evaluationUnit()]),
                    'data' => array_map(fn (float $value): float => round($value, 1), array_values($current)),
                    'borderColor' => $medium->chartColor(),
                    'backgroundColor' => $medium->chartColor().'26',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => __('energy.charts.previous_year'),
                    'data' => array_map(fn (float $value): float => round($value, 1), $previous),
                    'borderColor' => '#94a3b8',
                    'borderDash' => [5, 4],
                    'pointRadius' => 0,
                    'fill' => false,
                    'tension' => 0.3,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
