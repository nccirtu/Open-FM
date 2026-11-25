<?php

namespace App\Filament\Resources\PropertyResource\Pages;

use App\Filament\Concerns\AdoptsMediaFilesIntoFolder;
use App\Filament\Concerns\EnforcesManagerAssignment;
use App\Filament\Concerns\WithoutRelationManagers;
use App\Filament\Resources\PropertyResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditProperty extends EditRecord
{
    protected static string $resource = PropertyResource::class;

    use AdoptsMediaFilesIntoFolder;
    use EnforcesManagerAssignment;
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
