<?php

namespace App\Filament\Admin\Resources\SlaDefinitionResource\Pages;

use App\Filament\Admin\Resources\SlaDefinitionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSlaDefinitions extends ListRecords
{
    protected static string $resource = SlaDefinitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
