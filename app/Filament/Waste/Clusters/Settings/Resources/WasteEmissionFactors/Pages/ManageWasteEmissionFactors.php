<?php

namespace App\Filament\Waste\Clusters\Settings\Resources\WasteEmissionFactors\Pages;

use App\Filament\Waste\Clusters\Settings\Resources\WasteEmissionFactors\WasteEmissionFactorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageWasteEmissionFactors extends ManageRecords
{
    protected static string $resource = WasteEmissionFactorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
