<?php

namespace App\Filament\Energy\Widgets;

use App\Enums\EnergyMedium;
use App\Enums\MeterReadingStatus;
use App\Filament\Energy\Concerns\InteractsWithEnergyFilters;
use App\Filament\Energy\Resources\EnergyGoalResource;
use App\Models\EnergyGoal;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Services\Energy\EnergyReport;
use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class EnergyStatsWidget extends StatsOverviewWidget
{
    use InteractsWithEnergyFilters;

    public static int $gridW = 12;

    public static int $gridH = 4;

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Meter::class) ?? false;
    }

    protected function getColumns(): int|array|null
    {
        return ['md' => 3, 'xl' => 5];
    }

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $report = app(EnergyReport::class);
        $filters = $this->filters();
        [$previousFrom, $previousTo] = $filters->previousYear();
        $today = CarbonImmutable::today();

        $energyMeters = $filters->scope(Meter::query()->topLevel()->whereIn('medium', EnergyMedium::energyValues()));
        $energy = $report->consumption(clone $energyMeters, $filters->from, $filters->to);
        $energyBefore = $report->consumption(clone $energyMeters, $previousFrom, $previousTo);
        $water = $report->consumption($filters->scope($report->topLevelMeters(EnergyMedium::Water)), $filters->from, $filters->to);
        $waterBefore = $report->consumption($filters->scope($report->topLevelMeters(EnergyMedium::Water)), $previousFrom, $previousTo);

        $goals = EnergyGoal::query()->where('year', $filters->year())->with('goalable')->get()->filter(fn (EnergyGoal $goal): bool => $filters->includesGoal($goal));
        $onTrack = $goals->filter(fn (EnergyGoal $goal): bool => EnergyGoalResource::evaluation($goal)['status'] === 'good')->count();

        $suspicious = MeterReading::query()
            ->where('status', MeterReadingStatus::Suspicious->value)
            ->whereBetween('read_at', [$filters->from, $filters->to->endOfDay()])
            ->whereHas('meter', fn (Builder $meters): Builder => $filters->scope($meters))
            ->count();
        $calibration = $filters->scope(Meter::query())->whereDate('calibration_valid_until', '<=', $today->addDays(180))->count();

        return [
            Stat::make(__('energy.stats.energy_in_period'), number_format($energy, 0, ',', '.').' kWh')
                ->description(self::comparison($energy, $energyBefore))
                ->color($energyBefore > 0 ? ($energy > $energyBefore ? 'danger' : 'success') : 'gray'),
            Stat::make(__('energy.stats.water_in_period'), number_format($water, 1, ',', '.').' m³')
                ->description(self::comparison($water, $waterBefore))
                ->color($waterBefore > 0 ? ($water > $waterBefore ? 'danger' : 'success') : 'gray'),
            Stat::make(__('energy.stats.goals_on_track'), $goals->isEmpty() ? '—' : $onTrack.' / '.$goals->count())
                ->description((string) $filters->year())
                ->color($goals->isEmpty() || $onTrack === $goals->count() ? 'success' : 'warning'),
            Stat::make(__('energy.stats.suspicious_readings'), $suspicious)
                ->description($filters->label())
                ->color($suspicious > 0 ? 'danger' : 'success'),
            Stat::make(__('energy.stats.calibration_due'), $calibration)
                ->description(__('energy.stats.next_180_days'))
                ->color($calibration > 0 ? 'warning' : 'success'),
        ];
    }

    private static function comparison(float $current, float $previous): string
    {
        if ($previous <= 0) {
            return __('energy.stats.no_comparison');
        }

        $change = ($current - $previous) / $previous * 100;

        return __('energy.stats.vs_previous_year', ['change' => ($change >= 0 ? '+' : '').number_format($change, 1, ',', '.')]);
    }
}
