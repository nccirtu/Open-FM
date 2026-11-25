<?php

namespace App\Filament\Contracts\Pages;

use App\Filament\Contracts\Widgets\ContractCostsByTypeChartWidget;
use App\Filament\Contracts\Widgets\ContractsByTypeChartWidget;
use App\Filament\Contracts\Widgets\ContractStatsWidget;
use App\Filament\Contracts\Widgets\ExpiringContractsWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use JohnRivera7\FilamentWidgetGrid\Concerns\HasWidgetGrid;

class ContractsDashboard extends BaseDashboard
{
    use HasWidgetGrid;

    public function getWidgets(): array
    {
        return [
            ContractStatsWidget::class,
            ExpiringContractsWidget::class,
            ContractsByTypeChartWidget::class,
            ContractCostsByTypeChartWidget::class,
        ];
    }
}
