<?php

namespace App\Filament\Waste\Resources\WasteCollections\Pages;

use App\Filament\Imports\WasteCollectionImporter;
use App\Filament\Waste\Resources\WasteCollections\WasteCollectionResource;
use Filament\Actions\ImportAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;

class ListWasteCollections extends ListRecords
{
    protected static string $resource = WasteCollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->importer(WasteCollectionImporter::class)
                ->options(fn (): array => ['organization_id' => Filament::getTenant()?->getKey()])
                ->visible(fn (): bool => WasteCollectionResource::canCreate()),
            WasteCollectionResource::recordAction(),
        ];
    }
}
