<?php

namespace App\Filament\Fm\Resources\MaintenanceOrderResource\Pages;

use App\Filament\Concerns\CombinesDetailsWithRelationTabs;
use App\Filament\Fm\Resources\MaintenanceOrderResource;
use App\Support\Plugins\QrCodeBridge;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMaintenanceOrder extends ViewRecord
{
    protected static string $resource = MaintenanceOrderResource::class;

    use CombinesDetailsWithRelationTabs;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                MaintenanceOrderResource::completeAction(),
                EditAction::make(),
                QrCodeBridge::action(),
                DeleteAction::make(),
            ]),
        ];
    }
}
