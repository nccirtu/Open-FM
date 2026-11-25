<?php

namespace App\Filament\Portal\Resources\TicketResource\Pages;

use App\Filament\Portal\Resources\TicketResource;
use Filament\Resources\Pages\ViewRecord;

class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            TicketResource::confirmAction(),
            TicketResource::reopenAction(),
        ];
    }
}
