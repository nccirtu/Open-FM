<?php

namespace App\Filament\Waste\Clusters\Settings\Resources\WasteFractionGroups\Pages;

use App\Filament\Waste\Clusters\Settings\Resources\WasteFractionGroups\WasteFractionGroupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageWasteFractionGroups extends ManageRecords
{
    protected static string $resource = WasteFractionGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
