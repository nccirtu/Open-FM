<?php

namespace App\Filament\Waste\Resources\WasteGoals\Pages;

use App\Filament\Waste\Resources\WasteGoals\WasteGoalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageWasteGoals extends ManageRecords
{
    protected static string $resource = WasteGoalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
