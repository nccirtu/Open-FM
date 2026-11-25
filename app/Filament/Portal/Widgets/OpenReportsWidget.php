<?php

namespace App\Filament\Portal\Widgets;

use App\Enums\TicketStatus;
use App\Filament\Portal\Resources\TicketResource;
use App\Models\Ticket;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class OpenReportsWidget extends TableWidget
{
    public static int $gridW = 8;

    public static int $gridH = 12;

    public static function canView(): bool
    {
        return auth()->check();
    }

    public function getHeading(): ?string
    {
        return __('portal.widgets.open_reports');
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('portal.widgets.open_reports'))
            ->modelLabel(__('tickets.model'))
            ->pluralModelLabel(__('tickets.portal.navigation'))
            ->query(fn (): Builder => Ticket::query()
                ->reportedBy(auth()->user())
                ->whereIn('status', TicketStatus::open())
                ->with('property'))
            ->defaultSort('last_activity_at', 'desc')
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading(__('portal.widgets.open_reports_empty'))
            ->recordUrl(fn (Ticket $record): string => TicketResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('ticket_number')
                    ->label('#')
                    ->placeholder('—'),
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
                TextColumn::make('last_activity_at')
                    ->label(__('portal.columns.last_activity'))
                    ->since()
                    ->placeholder('—'),
            ]);
    }
}
