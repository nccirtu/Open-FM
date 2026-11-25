<?php

namespace App\Filament\Waste\Clusters\Settings\Resources\WasteTreatmentCategories\Pages;

use App\Filament\Waste\Clusters\Settings\Resources\WasteTreatmentCategories\WasteTreatmentCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageWasteTreatmentCategories extends ManageRecords
{
    protected static string $resource = WasteTreatmentCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
