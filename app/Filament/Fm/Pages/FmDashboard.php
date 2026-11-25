<?php

namespace App\Filament\Fm\Pages;

use App\Filament\Contracts\Widgets\ExpiringContractsWidget;
use App\Filament\Fm\Widgets\AssetConditionChartWidget;
use App\Filament\Fm\Widgets\AssetsByServiceStatusChartWidget;
use App\Filament\Fm\Widgets\AssetsByTypeChartWidget;
use App\Filament\Fm\Widgets\CostsPerMonthChartWidget;
use App\Filament\Fm\Widgets\MaintenanceCompletedChartWidget;
use App\Filament\Fm\Widgets\MaintenanceStatsWidget;
use App\Filament\Fm\Widgets\MapPluginRequiredWidget;
use App\Filament\Fm\Widgets\OpenTicketsWidget;
use App\Filament\Fm\Widgets\TicketsByCategoryChartWidget;
use App\Filament\Fm\Widgets\TicketsByPriorityChartWidget;
use App\Filament\Fm\Widgets\TicketsPerPropertyChartWidget;
use App\Filament\Fm\Widgets\TicketStatsWidget;
use App\Filament\Fm\Widgets\TicketTrendChartWidget;
use App\Filament\Fm\Widgets\UpcomingMaintenanceWidget;
use App\Filament\Integrations\MapWidget\PropertyMapWidget;
use App\Filament\Pm\Widgets\MyTasksWidget;
use App\Support\Plugins\Plugins;
use Filament\Pages\Dashboard as BaseDashboard;
use JohnRivera7\FilamentWidgetGrid\Concerns\HasWidgetGrid;

class FmDashboard extends BaseDashboard
{
    use HasWidgetGrid;

    public function getWidgets(): array
    {
        return [
            TicketStatsWidget::class,
            MaintenanceStatsWidget::class,
            Plugins::active('map-widget') ? PropertyMapWidget::class : MapPluginRequiredWidget::class,
            OpenTicketsWidget::class,
            UpcomingMaintenanceWidget::class,
            TicketTrendChartWidget::class,
            TicketsByPriorityChartWidget::class,
            TicketsByCategoryChartWidget::class,
            AssetConditionChartWidget::class,
            AssetsByServiceStatusChartWidget::class,
            TicketsPerPropertyChartWidget::class,
            AssetsByTypeChartWidget::class,
            CostsPerMonthChartWidget::class,
            MaintenanceCompletedChartWidget::class,
            ExpiringContractsWidget::class,
            MyTasksWidget::class,
        ];
    }
}
