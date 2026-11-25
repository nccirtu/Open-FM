<?php

namespace App\Filament\Waste\Resources\WastePickups;

use App\Enums\WastePickupKind;
use App\Enums\WastePickupStatus;
use App\Filament\Waste\Actions\PickupActions;
use App\Filament\Waste\Resources\WastePickups\Pages\ListWastePickups;
use App\Models\WastePickup;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WastePickupResource extends Resource
{
    protected static ?string $model = WastePickup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static ?int $navigationSort = 30;

    public static function getNavigationGroup(): ?string
    {
        return __('waste.navigation.operations');
    }

    public static function getModelLabel(): string
    {
        return __('waste.models.pickup.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('waste.models.pickup.plural');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['container.fraction', 'property', 'collection', 'ticket']))
            ->defaultSort('due_on')
            ->columns([
                TextColumn::make('due_on')
                    ->label(__('waste.fields.due_on'))
                    ->date('D d.m.Y')
                    ->placeholder(__('waste.pickup_status.requested'))
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('waste.fields.status'))
                    ->formatStateUsing(fn (WastePickup $record): string => $record->statusLabel())
                    ->badge(),
                TextColumn::make('container.number')
                    ->label(__('waste.models.container.singular'))
                    ->description(fn (WastePickup $record): ?string => $record->container?->fraction?->name)
                    ->searchable(),
                TextColumn::make('container.fraction.name')
                    ->label(__('waste.models.fraction.singular'))
                    ->badge()
                    ->color(fn (WastePickup $record): array => Color::hex($record->container?->fraction?->displayColor() ?? '#94a3b8'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('property.property_number')
                    ->label(__('property.model'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('kind')
                    ->label(__('waste.fields.kind'))
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
                TextColumn::make('collection.mass_kg')
                    ->label(__('waste.fields.mass_kg'))
                    ->numeric(decimalPlaces: 1, locale: 'de')
                    ->suffix(' kg')
                    ->description(fn (WastePickup $record): ?string => $record->collection?->mass_source?->getLabel())
                    ->placeholder('—'),
                TextColumn::make('confirmed_via')
                    ->label(__('waste.fields.confirmed_via'))
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('missed_reason')
                    ->label(__('waste.fields.missed_reason'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ticket.ticket_number')
                    ->label(__('waste.fields.ticket'))
                    ->prefix('#')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('waste.fields.status'))
                    ->options(WastePickupStatus::class)
                    ->multiple(),
                SelectFilter::make('kind')
                    ->label(__('waste.fields.kind'))
                    ->options(WastePickupKind::class),
                SelectFilter::make('property_id')
                    ->label(__('property.model'))
                    ->relationship('property', 'property_number')
                    ->searchable()
                    ->preload(),
                Filter::make('due_on')
                    ->schema([
                        DatePicker::make('from')->label(__('waste.filters.from'))->native(false),
                        DatePicker::make('until')->label(__('waste.filters.until'))->native(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('due_on', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('due_on', '<=', $date)))
                    ->columns(2),
            ])
            ->recordActions([
                ActionGroup::make(PickupActions::all()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    PickupActions::confirmBulk(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWastePickups::route('/'),
        ];
    }
}
