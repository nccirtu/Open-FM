<?php

namespace App\Filament\Waste\Clusters\Settings\Resources\WasteTreatments\Pages;

use App\Filament\Waste\Clusters\Settings\Resources\WasteTreatments\WasteTreatmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageWasteTreatments extends ManageRecords
{
    protected static string $resource = WasteTreatmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
