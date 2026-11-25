<?php

namespace App\Filament\Energy\Resources\MeterResource\Pages;

use App\Filament\Concerns\CombinesDetailsWithRelationTabs;
use App\Filament\Energy\Actions\RecordReadingAction;
use App\Filament\Energy\Resources\MeterResource;
use App\Filament\Energy\Resources\MeterResource\Widgets\MeterConsumersWidget;
use App\Filament\Energy\Resources\MeterResource\Widgets\MeterConsumptionChartWidget;
use App\Filament\Energy\Resources\MeterResource\Widgets\MeterStatsWidget;
use App\Support\Plugins\QrCodeBridge;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMeter extends ViewRecord
{
    use CombinesDetailsWithRelationTabs;

    protected static string $resource = MeterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            RecordReadingAction::make(),
            QrCodeBridge::action(),
            EditAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            MeterStatsWidget::class,
            MeterConsumptionChartWidget::class,
            MeterConsumersWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }
}
