<?php

namespace App\Filament\Waste\Widgets;

use App\Enums\WastePickupStatus;
use App\Filament\Waste\Concerns\InteractsWithWasteFilters;
use App\Models\WasteCollection;
use App\Models\WastePickup;
use App\Services\Waste\WasteReport;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class WasteStatsWidget extends StatsOverviewWidget
{
    use InteractsWithWasteFilters;

    protected static ?int $sort = 1;

    public static int $gridW = 12;

    public static int $gridH = 4;

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', WasteCollection::class) ?? false;
    }

    protected function getColumns(): int|array|null
    {
        return ['md' => 4, 'xl' => 7];
    }

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $report = app(WasteReport::class);
        $filters = $this->filters();
        [$previousFrom, $previousTo] = $filters->previousYear();

        $mass = $report->totalMass($filters) / 1000;
        $massBefore = $report->totalMass($filters, $previousFrom, $previousTo) / 1000;
        $recycling = $report->recyclingRate($filters);
        $separation = $report->separationRate($filters);
        $measured = $report->measuredShare($filters);
        $costPerTonne = $report->costPerTonne($filters);
        $missed = $report->missedCount($filters);
        $requests = $filters->scope(WastePickup::query())->where('status', WastePickupStatus::Requested->value)->count();
        $co2e = $report->co2e($filters);

        return [
            Stat::make(__('waste.stats.mass'), self::number($mass, 1).' t')
                ->description($massBefore > 0 ? self::change($mass, $massBefore) : __('waste.stats.no_comparison'))
                ->color($massBefore > 0 ? ($mass > $massBefore ? 'danger' : 'success') : 'gray'),
            Stat::make(__('waste.stats.recycling_rate'), $recycling !== null ? self::number($recycling, 0).' %' : '—')
                ->description(__('waste.stats.recycling_rate_help'))
                ->color('success'),
            Stat::make(__('waste.stats.separation_rate'), $separation !== null ? self::number($separation, 0).' %' : '—')
                ->description(__('waste.stats.separation_rate_help'))
                ->color($separation !== null && $separation < 90 ? 'warning' : 'success'),
            Stat::make(__('waste.stats.measured_share'), $measured !== null ? self::number($measured, 0).' %' : '—')
                ->description(__('waste.stats.measured_share_help'))
                ->color('gray'),
            Stat::make(__('waste.stats.cost_per_tonne'), $costPerTonne !== null ? self::number($costPerTonne, 0).' €' : '—')
                ->description($filters->label())
                ->color('gray'),
            Stat::make(__('waste.stats.co2e'), $co2e['coverage'] !== null ? self::number($co2e['kg'] / 1000, 1).' t' : '—')
                ->description($co2e['coverage'] !== null ? __('waste.stats.co2e_coverage', ['coverage' => self::number($co2e['coverage'], 0)]) : __('waste.stats.co2e_help'))
                ->color($co2e['coverage'] !== null && $co2e['coverage'] < 90 ? 'warning' : 'gray'),
            Stat::make(__('waste.stats.missed'), $missed)
                ->description(trans_choice('waste.stats.open_requests', $requests, ['count' => $requests]))
                ->color($missed > 0 ? 'danger' : 'success'),
        ];
    }

    private static function number(float $value, int $decimals): string
    {
        return number_format($value, $decimals, ',', '.');
    }

    private static function change(float $current, float $previous): string
    {
        $percent = ($current - $previous) / $previous * 100;

        return __('waste.stats.vs_previous_year', ['change' => ($percent > 0 ? '+' : '').self::number($percent, 0)]);
    }
}
