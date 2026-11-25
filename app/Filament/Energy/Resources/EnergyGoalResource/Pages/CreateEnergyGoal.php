<?php

namespace App\Filament\Energy\Resources\EnergyGoalResource\Pages;

use App\Filament\Energy\Resources\EnergyGoalResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEnergyGoal extends CreateRecord
{
    protected static string $resource = EnergyGoalResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
