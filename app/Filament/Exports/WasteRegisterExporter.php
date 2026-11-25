<?php

namespace App\Filament\Exports;

use App\Models\WasteCollection;
use App\Services\Waste\HazardousWasteRegister;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class WasteRegisterExporter extends Exporter
{
    protected static ?string $model = WasteCollection::class;

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with(['property', 'fraction', 'company', 'treatment']);
    }

    public static function getColumns(): array
    {
        return array_map(fn (ExportColumn $column): ExportColumn => $column->preventFormulaInjection(), [
            ExportColumn::make('collected_at')->label(__('waste.fields.collected_at'))->formatStateUsing(fn (WasteCollection $record): string => $record->collected_at->format('d.m.Y')),
            ExportColumn::make('property.property_number')->label(__('property.model')),
            ExportColumn::make('fraction.code')->label(__('waste.fields.waste_code')),
            ExportColumn::make('fraction.name')->label(__('waste.models.fraction.singular')),
            ExportColumn::make('mass_kg')->label(__('waste.fields.mass_kg')),
            ExportColumn::make('mass_source')->label(__('waste.fields.mass_source'))->formatStateUsing(fn (WasteCollection $record): ?string => $record->mass_source?->getLabel()),
            ExportColumn::make('company.name')->label(__('waste.fields.company')),
            ExportColumn::make('treatment.code')->label(__('waste.models.treatment.singular')),
            ExportColumn::make('document_number')->label(__('waste.fields.document_number')),
            ExportColumn::make('certificate_number')->label(__('waste.fields.certificate_number')),
            ExportColumn::make('register_deadline')->label(__('waste.fields.register_deadline'))->state(fn (WasteCollection $record): ?string => app(HazardousWasteRegister::class)->deadline($record)?->format('d.m.Y')),
            ExportColumn::make('retain_until')->label(__('waste.fields.retain_until'))->state(fn (WasteCollection $record): ?string => app(HazardousWasteRegister::class)->retainUntil($record)?->format('d.m.Y')),
            ExportColumn::make('note')->label(__('waste.fields.note')),
        ]);
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return trans_choice('waste.import.register_exported', $export->successful_rows, ['count' => number_format($export->successful_rows, 0, ',', '.')]);
    }
}
