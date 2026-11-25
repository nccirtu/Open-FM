<?php

namespace App\Filament\Waste\Clusters\Settings\Resources\WasteFractions\Pages;

use App\Filament\Waste\Clusters\Settings\Resources\WasteFractions\WasteFractionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageWasteFractions extends ManageRecords
{
    protected static string $resource = WasteFractionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
