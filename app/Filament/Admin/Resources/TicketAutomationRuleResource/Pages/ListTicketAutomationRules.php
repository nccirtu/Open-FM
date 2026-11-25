<?php

namespace App\Filament\Admin\Resources\TicketAutomationRuleResource\Pages;

use App\Filament\Admin\Resources\TicketAutomationRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTicketAutomationRules extends ListRecords
{
    protected static string $resource = TicketAutomationRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
