<?php

namespace App\Filament\Portal\Widgets;

use App\Enums\TicketStatus;
use App\Filament\Portal\Resources\TicketResource;
use App\Models\Ticket;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class AwaitingConfirmationWidget extends TableWidget
{
    public static int $gridW = 6;

    public static int $gridH = 10;

    public static function canView(): bool
    {
        return auth()->check();
    }

    public function getHeading(): ?string
    {
        return __('portal.widgets.awaiting');
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('portal.widgets.awaiting'))
            ->modelLabel(__('tickets.model'))
            ->pluralModelLabel(__('tickets.portal.navigation'))
            ->query(fn (): Builder => Ticket::query()
                ->reportedBy(auth()->user())
                ->where('status', TicketStatus::Resolved)
                ->with('property'))
            ->defaultSort('resolved_at', 'desc')
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading(__('portal.widgets.awaiting_empty'))
            ->recordUrl(fn (Ticket $record): string => TicketResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('title')
                    ->label(__('tickets.fields.title'))
                    ->description(fn (Ticket $record): ?string => $record->property?->property_number)
                    ->limit(36),
                TextColumn::make('resolved_at')
                    ->label(__('portal.widgets.resolved_at'))
                    ->since()
                    ->placeholder('—'),
            ])
            ->recordActions([
                TicketResource::confirmAction()->iconButton()->tooltip(__('tickets.portal.confirm.action')),
                TicketResource::reopenAction()->iconButton()->tooltip(__('tickets.portal.reopen.action')),
            ]);
    }
}
