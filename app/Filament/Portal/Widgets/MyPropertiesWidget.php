<?php

namespace App\Filament\Portal\Widgets;

use App\Enums\TicketStatus;
use App\Filament\Portal\Resources\TicketResource;
use App\Models\Property;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class MyPropertiesWidget extends TableWidget
{
    public static int $gridW = 4;

    public static int $gridH = 10;

    public static function canView(): bool
    {
        return auth()->check();
    }

    public function getHeading(): ?string
    {
        return __('portal.widgets.properties');
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('portal.widgets.properties'))
            ->modelLabel(__('property.model'))
            ->pluralModelLabel(__('portal.widgets.properties'))
            ->query(function (): Builder {
                $user = auth()->user();
                $assignedIds = $user->assignedPropertyIds();

                return Property::query()
                    ->when($assignedIds !== null, fn (Builder $query) => $query->whereKey($assignedIds))
                    ->withCount(['tickets as open_reports_count' => fn (Builder $query) => $query
                        ->visibleInPortal($user)
                        ->whereIn('status', TicketStatus::open())]);
            })
            ->defaultSort('open_reports_count', 'desc')
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->columns([
                TextColumn::make('property_number')
                    ->label(__('tickets.fields.property'))
                    ->description(fn (Property $record): ?string => $record->city)
                    ->searchable(),
                TextColumn::make('open_reports_count')
                    ->label(__('portal.widgets.open_count'))
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'warning' : 'gray')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('report')
                    ->label(__('tickets.portal.report'))
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->url(fn (Property $record): string => TicketResource::getUrl('create', ['property_id' => $record->getKey()])),
            ]);
    }
}
