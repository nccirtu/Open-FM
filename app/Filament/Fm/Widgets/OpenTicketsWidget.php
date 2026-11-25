<?php

namespace App\Filament\Fm\Widgets;

use App\Enums\TicketStatus;
use App\Filament\Fm\Resources\TicketResource;
use App\Models\Ticket;
use App\Support\ServicePartner;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Model;

class OpenTicketsWidget extends TableWidget
{
    public static int $gridW = 6;

    public static int $gridH = 12;

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user !== null && ! ServicePartner::isExternal($user) && $user->can('viewAny', Ticket::class);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('app.widgets.open_tickets'))
            ->modelLabel(__('tickets.model'))
            ->pluralModelLabel(__('tickets.models'))
            ->query(
                Ticket::query()
                    ->whereIn('status', TicketStatus::open())
                    ->with('property')
                    ->latest()
            )
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->columns([
                TextColumn::make('title')
                    ->label(__('tickets.fields.title'))
                    ->limit(40)
                    ->searchable(),
                TextColumn::make('property.property_number')
                    ->label(__('tickets.fields.property')),
                TextColumn::make('priority')
                    ->label(__('tickets.fields.priority'))
                    ->badge(),
                TextColumn::make('status')
                    ->label(__('tickets.fields.status'))
                    ->badge(),
                TextColumn::make('created_at')
                    ->label(__('tickets.fields.created_at'))
                    ->since(),
            ])
            ->recordUrl(fn (Model $record): string => TicketResource::getUrl('view', ['record' => $record]));
    }
}
