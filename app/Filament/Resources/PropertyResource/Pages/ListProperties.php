<?php

namespace App\Filament\Resources\PropertyResource\Pages;

use App\Filament\Concerns\ListsManagedRecords;
use App\Filament\Resources\PropertyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProperties extends ListRecords
{
    use ListsManagedRecords;

    protected static string $resource = PropertyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function mineTabLabel(): string
    {
        return __('property.tabs.mine');
    }

    protected function allTabLabel(): string
    {
        return __('property.tabs.all');
    }
}
