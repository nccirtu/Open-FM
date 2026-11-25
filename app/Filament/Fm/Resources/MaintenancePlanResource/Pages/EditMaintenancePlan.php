<?php

namespace App\Filament\Fm\Resources\MaintenancePlanResource\Pages;

use App\Filament\Concerns\WithoutRelationManagers;
use App\Filament\Fm\Resources\MaintenancePlanResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMaintenancePlan extends EditRecord
{
    protected static string $resource = MaintenancePlanResource::class;

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
