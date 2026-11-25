<?php

namespace App\Filament\Waste\Resources\WasteEvidence\Pages;

use App\Filament\Waste\Resources\WasteEvidence\WasteEvidenceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageWasteEvidence extends ManageRecords
{
    protected static string $resource = WasteEvidenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
