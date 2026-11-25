<?php

namespace App\Filament\Portal\Resources\TicketResource\Pages;

use App\Enums\TicketStatus;
use App\Filament\Portal\Resources\TicketResource;
use App\Models\Ticket;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTickets extends ListRecords
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('tickets.portal.report')),
        ];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $user = auth()->user();

        $tabs = [
            'open' => Tab::make(__('portal.tabs.open'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->reportedBy($user)->whereIn('status', TicketStatus::open())),
            'awaiting' => Tab::make(__('portal.tabs.awaiting'))
                ->badge(fn (): ?int => Ticket::query()->reportedBy($user)->where('status', TicketStatus::Resolved)->count() ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->reportedBy($user)->where('status', TicketStatus::Resolved)),
            'closed' => Tab::make(__('portal.tabs.closed'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->reportedBy($user)->where('status', TicketStatus::Closed)),
            'all' => Tab::make(__('portal.tabs.all'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->reportedBy($user)),
        ];

        if ($user?->assignedPropertyIds() !== null) {
            $tabs['properties'] = Tab::make(__('portal.tabs.properties'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->onAssignedProperties($user));
        }

        return $tabs;
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'open';
    }
}
