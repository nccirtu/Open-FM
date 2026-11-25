<?php

namespace App\Filament\Fm\Resources\CompanyResource\Pages;

use App\Filament\Concerns\WithoutRelationManagers;
use App\Filament\Fm\Resources\CompanyResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCompany extends EditRecord
{
    protected static string $resource = CompanyResource::class;

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
