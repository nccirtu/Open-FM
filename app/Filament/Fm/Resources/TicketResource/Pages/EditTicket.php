<?php

namespace App\Filament\Fm\Resources\TicketResource\Pages;

use App\Filament\Concerns\AdoptsMediaFilesIntoFolder;
use App\Filament\Concerns\WithoutRelationManagers;
use App\Filament\Fm\Resources\TicketResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;

    use AdoptsMediaFilesIntoFolder;
    use WithoutRelationManagers;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->getRecord()->forceFill(['seen_at' => now()])->saveQuietly();
    }

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
