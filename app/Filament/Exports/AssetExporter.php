<?php

namespace App\Filament\Exports;

use App\Models\Asset;
use App\Support\Plugins\CustomFieldsBridge;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class AssetExporter extends Exporter
{
    protected static ?string $model = Asset::class;

    public static function getColumns(): array
    {
        return array_map(fn (ExportColumn $column): ExportColumn => $column->preventFormulaInjection(), [
            ExportColumn::make('id')
                ->label(__('assets.fields.id')),
            ExportColumn::make('name')->label(__('assets.fields.name')),
            ExportColumn::make('property.property_number')->label(__('property.model')),
            ExportColumn::make('manufacturer')->label(__('assets.fields.manufacturer')),
            ExportColumn::make('company.name')->label(__('assets.fields.maintenance_partner')),
            ExportColumn::make('inspectionCompany.name')->label(__('assets.fields.inspection_company')),
            ExportColumn::make('serial_number')->label(__('assets.fields.serial_number')),
            ExportColumn::make('last_maintenance_at')->label(__('assets.fields.last_maintenance')),
            ExportColumn::make('next_maintenance_at')->label(__('assets.fields.next_maintenance')),
            ExportColumn::make('assetType.name')->label(__('assets.fields.type'))->formatStateUsing(fn (Asset $record): ?string => $record->assetType?->displayLabelForCurrentTenant()),
            ...CustomFieldsBridge::exportColumns('asset'),
        ]);
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Dein Export Assets ist beendet und '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' wurden exportiert.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
