<?php

namespace App\Filament\Energy\Resources\MeterResource\Widgets;

use App\Models\Meter;
use App\Services\Energy\EnergyReport;
use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class MeterStatsWidget extends StatsOverviewWidget
{
    public ?Model $record = null;

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        if (! $this->record instanceof Meter) {
            return [];
        }

        $report = app(EnergyReport::class);
        $today = CarbonImmutable::today();
        $unit = $this->record->evaluationUnit();
        $current = $report->consumption([$this->record], $today->startOfYear(), $today);
        $previous = $report->consumption([$this->record], $today->subYear()->startOfYear(), $today->subYear());
        $change = $previous > 0 ? ($current - $previous) / $previous * 100 : null;
        $last = $this->record->latestReading;

        return [
            Stat::make(__('energy.stats.year_to_date', ['year' => $today->year]), self::format($current).' '.$unit)
                ->description($change === null ? __('energy.stats.no_comparison') : __('energy.stats.vs_previous_year', ['change' => ($change >= 0 ? '+' : '').number_format($change, 1, ',', '.')]))
                ->color($change === null ? 'gray' : ($change > 0 ? 'danger' : 'success')),
            Stat::make(__('energy.stats.previous_year_to_date', ['year' => $today->year - 1]), self::format($previous).' '.$unit),
            Stat::make(__('energy.fields.last_reading'), $last ? number_format((float) $last->value, 1, ',', '.').' '.$this->record->unit : '—')
                ->description($last?->read_at->format('d.m.Y H:i').($last ? ' · '.$last->source->getLabel() : ''))
                ->color($last?->status->getColor() ?? 'gray'),
        ];
    }

    public static function format(float $value): string
    {
        return number_format($value, $value < 100 ? 1 : 0, ',', '.');
    }
}
