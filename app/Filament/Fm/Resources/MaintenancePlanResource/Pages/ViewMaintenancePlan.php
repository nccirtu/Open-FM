<?php

namespace App\Filament\Fm\Resources\MaintenancePlanResource\Pages;

use App\Filament\Concerns\CombinesDetailsWithRelationTabs;
use App\Filament\Fm\Resources\MaintenancePlanResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMaintenancePlan extends ViewRecord
{
    protected static string $resource = MaintenancePlanResource::class;

    use CombinesDetailsWithRelationTabs;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                EditAction::make(),
                DeleteAction::make(),
            ]),
        ];
    }
}
