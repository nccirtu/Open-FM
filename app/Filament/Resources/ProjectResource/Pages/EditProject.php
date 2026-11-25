<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Concerns\AdoptsMediaFilesIntoFolder;
use App\Filament\Concerns\EnforcesManagerAssignment;
use App\Filament\Concerns\WithoutRelationManagers;
use App\Filament\Resources\ProjectResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    use AdoptsMediaFilesIntoFolder;
    use EnforcesManagerAssignment;
    use WithoutRelationManagers;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make(),
                Action::make('board')
                    ->label(__('projects.tasks.board'))
                    ->icon('heroicon-o-view-columns')
                    ->url(fn (): string => ProjectBoard::getUrl(['record' => $this->getRecord()])),
                DeleteAction::make(),
            ]),
        ];
    }
}
