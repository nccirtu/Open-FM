<?php

namespace App\Filament\RelationManagers;

use App\Filament\Fm\Resources\TicketResource;
use App\Models\Ticket;
use App\Support\ServicePartner;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TicketsRelationManager extends RelationManager
{
    protected static string $relationship = 'tickets';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('tickets.models');
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->tickets()->count();

        return $count > 0 ? (string) $count : null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => ServicePartner::restrict($query, auth()->user()))
            ->recordTitleAttribute('title')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('ticket_number')->label('#')->placeholder('—')->sortable(),
                TextColumn::make('title')->label(__('tickets.fields.title'))->searchable()->limit(60)->weight('medium'),
                TextColumn::make('priority')->label(__('tickets.fields.priority'))->badge(),
                TextColumn::make('status')->label(__('tickets.fields.status'))->badge(),
                TextColumn::make('assignee.name')->label(__('tickets.fields.assignee'))->placeholder('—'),
                TextColumn::make('created_at')->label(__('tickets.fields.created_at'))->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->recordActions([
                Action::make('open')
                    ->label(__('filament-actions::view.single.label'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Ticket $record): string => TicketResource::getUrl('view', ['record' => $record], panel: 'fm')),
            ]);
    }
}
