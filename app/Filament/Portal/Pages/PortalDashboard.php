<?php

namespace App\Filament\Portal\Pages;

use App\Filament\Portal\Resources\TicketResource;
use App\Filament\Portal\Widgets\AwaitingConfirmationWidget;
use App\Filament\Portal\Widgets\MyPropertiesWidget;
use App\Filament\Portal\Widgets\MyReportsStatsWidget;
use App\Filament\Portal\Widgets\OpenReportsWidget;
use App\Filament\Portal\Widgets\ReportNewsWidget;
use App\Filament\Portal\Widgets\ReportsByCategoryChartWidget;
use App\Filament\Portal\Widgets\ReportsByStatusChartWidget;
use App\Filament\Portal\Widgets\ReportsTrendChartWidget;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;
use JohnRivera7\FilamentWidgetGrid\Concerns\HasWidgetGrid;

class PortalDashboard extends BaseDashboard
{
    use HasWidgetGrid;

    public function getWidgets(): array
    {
        return [
            MyReportsStatsWidget::class,
            OpenReportsWidget::class,
            ReportsByStatusChartWidget::class,
            AwaitingConfirmationWidget::class,
            ReportNewsWidget::class,
            MyPropertiesWidget::class,
            ReportsTrendChartWidget::class,
            ReportsByCategoryChartWidget::class,
        ];
    }

    protected function getAdditionalHeaderActions(): array
    {
        return [
            Action::make('report')
                ->label(__('tickets.portal.report'))
                ->icon(Heroicon::OutlinedPlus)
                ->url(fn (): string => TicketResource::getUrl('create')),
        ];
    }
}
