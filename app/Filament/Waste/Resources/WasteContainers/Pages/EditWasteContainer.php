<?php

namespace App\Filament\Waste\Resources\WasteContainers\Pages;

use App\Filament\Actions\ShowInBimAction;
use App\Filament\Waste\Resources\WasteContainers\WasteContainerResource;
use App\Support\Plugins\QrCodeBridge;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWasteContainer extends EditRecord
{
    protected static string $resource = WasteContainerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            WasteContainerResource::requestPickupAction(),
            QrCodeBridge::action(),
            ShowInBimAction::make(),
            DeleteAction::make(),
        ];
    }
}
