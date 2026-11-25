<?php

namespace App\Filament\Energy\Resources\EnergyGoalResource\Pages;

use App\Filament\Energy\Resources\EnergyGoalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEnergyGoals extends ListRecords
{
    protected static string $resource = EnergyGoalResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
