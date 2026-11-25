<?php

namespace App\Filament\Waste\Resources\WasteContainers\Pages;

use App\Filament\Imports\WasteContainerImporter;
use App\Filament\Waste\Resources\WasteContainers\WasteContainerResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;

class ListWasteContainers extends ListRecords
{
    protected static string $resource = WasteContainerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->importer(WasteContainerImporter::class)
                ->options(fn (): array => ['organization_id' => Filament::getTenant()?->getKey()])
                ->visible(fn (): bool => WasteContainerResource::canCreate()),
            CreateAction::make(),
        ];
    }
}
