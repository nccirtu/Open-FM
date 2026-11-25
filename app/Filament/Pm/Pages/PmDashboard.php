<?php

namespace App\Filament\Pm\Pages;

use App\Filament\Pm\Widgets\MyTasksWidget;
use App\Filament\Pm\Widgets\ProjectBudgetChartWidget;
use App\Filament\Pm\Widgets\ProjectHealthChartWidget;
use App\Filament\Pm\Widgets\ProjectPortfolioWidget;
use App\Filament\Pm\Widgets\ProjectStatsWidget;
use App\Filament\Pm\Widgets\TasksByStatusChartWidget;
use App\Filament\Pm\Widgets\UpcomingMilestonesWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use JohnRivera7\FilamentWidgetGrid\Concerns\HasWidgetGrid;

class PmDashboard extends BaseDashboard
{
    use HasWidgetGrid;

    public function getWidgets(): array
    {
        return [
            ProjectStatsWidget::class,
            ProjectPortfolioWidget::class,
            MyTasksWidget::class,
            UpcomingMilestonesWidget::class,
            TasksByStatusChartWidget::class,
            ProjectHealthChartWidget::class,
            ProjectBudgetChartWidget::class,
        ];
    }
}
