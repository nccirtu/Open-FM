<?php

namespace App\Filament\Contracts\Resources\ContractResource\Pages;

use App\Filament\Concerns\AdoptsMediaFilesIntoFolder;
use App\Filament\Concerns\WithoutRelationManagers;
use App\Filament\Contracts\Resources\ContractResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditContract extends EditRecord
{
    protected static string $resource = ContractResource::class;

    use AdoptsMediaFilesIntoFolder;
    use WithoutRelationManagers;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make(),
                DeleteAction::make(),
            ]),
        ];
    }
}
