<?php

namespace App\Filament\Energy\Resources\MeterResource\Pages;

use App\Filament\Energy\Resources\MeterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMeters extends ListRecords
{
    protected static string $resource = MeterResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
