<?php

namespace App\Filament\Fm\Resources\TicketResource\Pages;

use App\Filament\Concerns\CombinesDetailsWithRelationTabs;
use App\Filament\Fm\Resources\TicketResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    use CombinesDetailsWithRelationTabs;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->getRecord()->forceFill(['seen_at' => now()])->saveQuietly();
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                TicketResource::processAction(),
                EditAction::make(),
                DeleteAction::make(),
            ]),
        ];
    }
}
