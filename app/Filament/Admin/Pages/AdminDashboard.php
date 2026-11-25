<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\OrganizationsOverviewWidget;
use App\Filament\Admin\Widgets\PluginStatusWidget;
use App\Filament\Admin\Widgets\SystemHealthWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use JohnRivera7\FilamentWidgetGrid\Concerns\HasWidgetGrid;

class AdminDashboard extends BaseDashboard
{
    use HasWidgetGrid;

    public function getWidgets(): array
    {
        return [
            SystemHealthWidget::class,
            OrganizationsOverviewWidget::class,
            PluginStatusWidget::class,
        ];
    }
}
