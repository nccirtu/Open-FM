<?php

namespace App\Filament\Contracts\Resources\ContractResource\Pages;

use App\Filament\Concerns\CombinesDetailsWithRelationTabs;
use App\Filament\Contracts\Resources\ContractResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewContract extends ViewRecord
{
    protected static string $resource = ContractResource::class;

    use CombinesDetailsWithRelationTabs;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                EditAction::make(),
                DeleteAction::make(),
            ]),
        ];
    }
}
