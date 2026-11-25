<?php

namespace App\Filament\Fm\Resources\MaintenanceOrderResource\Pages;

use App\Filament\Concerns\WithoutRelationManagers;
use App\Filament\Fm\Resources\MaintenanceOrderResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMaintenanceOrder extends EditRecord
{
    protected static string $resource = MaintenanceOrderResource::class;

    use WithoutRelationManagers;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make(),
                DeleteAction::make(),
            ]),
        ];
    }
}
