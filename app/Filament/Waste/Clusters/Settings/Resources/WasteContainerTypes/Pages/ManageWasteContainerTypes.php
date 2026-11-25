<?php

namespace App\Filament\Waste\Clusters\Settings\Resources\WasteContainerTypes\Pages;

use App\Filament\Waste\Clusters\Settings\Resources\WasteContainerTypes\WasteContainerTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageWasteContainerTypes extends ManageRecords
{
    protected static string $resource = WasteContainerTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
