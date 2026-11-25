<?php

namespace App\Filament\Energy\Resources\MeterReadingResource\Pages;

use App\Filament\Energy\Pages\RecordReadingPage;
use App\Filament\Energy\Resources\MeterReadingResource;
use App\Filament\Imports\MeterReadingImporter;
use App\Models\MeterReading;
use Filament\Actions\Action;
use Filament\Actions\ImportAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;

class ListMeterReadings extends ListRecords
{
    protected static string $resource = MeterReadingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->label(__('energy.actions.import_readings'))
                ->importer(MeterReadingImporter::class)
                ->options(fn (): array => ['organization_id' => Filament::getTenant()?->getKey()])
                ->visible(fn (): bool => auth()->user()?->can('create', MeterReading::class) ?? false),
            Action::make('recordReadings')
                ->label(__('energy.pages.record_readings'))
                ->icon('heroicon-o-device-phone-mobile')
                ->url(fn (): string => RecordReadingPage::getUrl()),
        ];
    }
}
