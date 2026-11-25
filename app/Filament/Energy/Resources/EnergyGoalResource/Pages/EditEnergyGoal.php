<?php

namespace App\Filament\Energy\Resources\EnergyGoalResource\Pages;

use App\Filament\Energy\Resources\EnergyGoalResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEnergyGoal extends EditRecord
{
    protected static string $resource = EnergyGoalResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
