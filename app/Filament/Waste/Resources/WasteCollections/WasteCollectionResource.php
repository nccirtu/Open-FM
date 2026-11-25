<?php

namespace App\Filament\Waste\Resources\WasteCollections;

use App\Enums\WasteCollectionSource;
use App\Enums\WasteMassSource;
use App\Filament\Waste\Resources\WasteCollections\Pages\ListWasteCollections;
use App\Models\Property;
use App\Models\WasteCollection;
use App\Models\WasteContainer;
use App\Models\WasteFraction;
use App\Models\WasteTreatment;
use App\Services\Waste\WasteCollectionRecorder;
use App\Support\Uploads;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class WasteCollectionResource extends Resource
{
    protected static ?string $model = WasteCollection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static ?int $navigationSort = 40;

    public static function getNavigationGroup(): ?string
    {
        return __('waste.navigation.operations');
    }

    public static function getModelLabel(): string
    {
        return __('waste.models.collection.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('waste.models.collection.plural');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['container', 'fraction', 'property', 'treatment']))
            ->defaultSort('collected_at', 'desc')
            ->columns([
                TextColumn::make('collected_at')
                    ->label(__('waste.fields.collected_at'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('property.property_number')
                    ->label(__('property.model'))
                    ->description(fn (WasteCollection $record): ?string => $record->container?->number)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('fraction.name')
                    ->label(__('waste.models.fraction.singular'))
                    ->badge()
                    ->color(fn (WasteCollection $record): array => Color::hex($record->fraction?->displayColor() ?? '#94a3b8')),
                TextColumn::make('mass_kg')
                    ->label(__('waste.fields.mass_kg'))
                    ->numeric(decimalPlaces: 1, locale: 'de')
                    ->suffix(' kg')
                    ->sortable()
                    ->summarize(Sum::make()->numeric(decimalPlaces: 0, locale: 'de')->suffix(' kg')),
                TextColumn::make('mass_source')
                    ->label(__('waste.fields.mass_source'))
                    ->badge(),
                TextColumn::make('fill_level_pct')
                    ->label(__('waste.fields.fill_level'))
                    ->suffix(' %')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('treatment.code')
                    ->label(__('waste.models.treatment.singular'))
                    ->fontFamily('mono')
                    ->tooltip(fn (WasteCollection $record): ?string => $record->treatment?->name)
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('source')
                    ->label(__('waste.fields.source'))
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
                TextColumn::make('receipt')
                    ->label(__('waste.fields.receipt'))
                    ->formatStateUsing(fn (): string => __('waste.actions.open_receipt'))
                    ->url(fn (WasteCollection $record): ?string => filled($record->receipt) ? Uploads::url($record->receipt) : null, shouldOpenInNewTab: true)
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('property_id')
                    ->label(__('property.model'))
                    ->relationship('property', 'property_number')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('waste_fraction_id')
                    ->label(__('waste.models.fraction.singular'))
                    ->relationship('fraction', 'name')
                    ->preload(),
                SelectFilter::make('mass_source')
                    ->label(__('waste.fields.mass_source'))
                    ->options(WasteMassSource::class),
                SelectFilter::make('source')
                    ->label(__('waste.fields.source'))
                    ->options(WasteCollectionSource::class),
                Filter::make('collected_at')
                    ->schema([
                        DateTimePicker::make('from')->label(__('waste.filters.from'))->native(false)->time(false),
                        DateTimePicker::make('until')->label(__('waste.filters.until'))->native(false)->time(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('collected_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('collected_at', '<=', $date)))
                    ->columns(2),
            ])
            ->recordActions([
                ActionGroup::make([
                    self::editAction(),
                    self::deleteAction(),
                ]),
            ]);
    }

    public static function recordAction(): Action
    {
        return Action::make('recordCollection')
            ->label(__('waste.actions.record_collection'))
            ->icon('heroicon-o-plus')
            ->authorize(fn (): bool => static::canCreate())
            ->modalWidth('2xl')
            ->schema([
                ToggleButtons::make('mode')
                    ->label(__('waste.fields.record_for'))
                    ->options([
                        'container' => __('waste.models.container.singular'),
                        'property' => __('waste.fields.property_and_fraction'),
                    ])
                    ->default('container')
                    ->inline()
                    ->live(),
                Select::make('container')
                    ->label(__('waste.models.container.singular'))
                    ->options(fn (): array => WasteContainer::query()->active()->with('fraction')->orderBy('number')->get()->mapWithKeys(fn (WasteContainer $container): array => [$container->getKey() => $container->label()])->all())
                    ->searchable()
                    ->required(fn (Get $get): bool => $get('mode') === 'container')
                    ->visible(fn (Get $get): bool => $get('mode') === 'container'),
                Grid::make(2)
                    ->visible(fn (Get $get): bool => $get('mode') === 'property')
                    ->schema([
                        Select::make('property')
                            ->label(__('property.model'))
                            ->options(fn (): array => Property::query()->orderBy('property_number')->pluck('property_number', 'id')->all())
                            ->searchable()
                            ->required(fn (Get $get): bool => $get('mode') === 'property'),
                        Select::make('fraction')
                            ->label(__('waste.models.fraction.singular'))
                            ->options(fn (): array => WasteFraction::query()->active()->orderBy('sort')->get()->mapWithKeys(fn (WasteFraction $fraction): array => [$fraction->getKey() => $fraction->label()])->all())
                            ->searchable()
                            ->required(fn (Get $get): bool => $get('mode') === 'property'),
                    ]),
                ...self::collectionFields(),
            ])
            ->action(function (array $data): void {
                $recorder = app(WasteCollectionRecorder::class);
                $attributes = self::attributes($data);

                try {
                    if ($data['mode'] === 'container') {
                        $recorder->recordForContainer(
                            WasteContainer::query()->findOrFail($data['container']),
                            CarbonImmutable::parse($data['collected_at']),
                            WasteCollectionSource::Manual,
                            filled($data['mass_kg'] ?? null) ? (float) $data['mass_kg'] : null,
                            filled($data['fill_level_pct'] ?? null) ? (int) $data['fill_level_pct'] : null,
                            $attributes,
                        );
                    } else {
                        $recorder->recordForProperty(
                            Property::query()->findOrFail($data['property']),
                            WasteFraction::query()->findOrFail($data['fraction']),
                            CarbonImmutable::parse($data['collected_at']),
                            (float) ($data['mass_kg'] ?? 0),
                            WasteCollectionSource::Manual,
                            $attributes,
                        );
                    }
                } catch (InvalidArgumentException $exception) {
                    Notification::make()->danger()->title($exception->getMessage())->send();

                    return;
                }

                Notification::make()->success()->title(__('waste.notifications.collection_saved'))->send();
            });
    }

    public static function editAction(): Action
    {
        return Action::make('editCollection')
            ->label(__('filament-actions::edit.single.label'))
            ->icon('heroicon-o-pencil-square')
            ->authorize(fn (WasteCollection $record): bool => auth()->user()?->can('update', $record) ?? false)
            ->fillForm(fn (WasteCollection $record): array => [
                'collected_at' => $record->collected_at,
                'mass_kg' => $record->mass_kg,
                'fill_level_pct' => $record->fill_level_pct,
                'waste_treatment_id' => $record->waste_treatment_id,
                'receipt' => $record->receipt,
                'note' => $record->note,
            ])
            ->schema(self::collectionFields())
            ->action(function (WasteCollection $record, array $data): void {
                $massChanged = filled($data['mass_kg'] ?? null) && (float) $data['mass_kg'] !== (float) $record->mass_kg;

                app(WasteCollectionRecorder::class)->update(
                    $record,
                    CarbonImmutable::parse($data['collected_at']),
                    $massChanged ? (float) $data['mass_kg'] : null,
                    filled($data['fill_level_pct'] ?? null) ? (int) $data['fill_level_pct'] : null,
                    self::attributes($data),
                );

                Notification::make()->success()->title(__('waste.notifications.collection_saved'))->send();
            });
    }

    public static function deleteAction(): Action
    {
        return Action::make('deleteCollection')
            ->label(__('filament-actions::delete.single.label'))
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->authorize(fn (WasteCollection $record): bool => auth()->user()?->can('delete', $record) ?? false)
            ->action(fn (WasteCollection $record) => app(WasteCollectionRecorder::class)->delete($record));
    }

    /**
     * @return array<int, mixed>
     */
    private static function collectionFields(): array
    {
        return [
            Grid::make(2)->schema([
                DateTimePicker::make('collected_at')
                    ->label(__('waste.fields.collected_at'))
                    ->seconds(false)
                    ->default(now())
                    ->required(),
                Select::make('waste_treatment_id')
                    ->label(__('waste.models.treatment.singular'))
                    ->helperText(__('waste.help.treatment_default'))
                    ->options(fn (): array => WasteTreatment::query()->orderBy('sort')->get()->mapWithKeys(fn (WasteTreatment $treatment): array => [$treatment->getKey() => $treatment->label()])->all())
                    ->searchable(),
                TextInput::make('mass_kg')
                    ->label(__('waste.fields.mass_kg'))
                    ->helperText(__('waste.help.mass_or_fill'))
                    ->numeric()
                    ->minValue(0)
                    ->suffix('kg')
                    ->required(fn (Get $get): bool => $get('mode') === 'property'),
                TextInput::make('fill_level_pct')
                    ->label(__('waste.fields.fill_level'))
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->suffix('%')
                    ->visible(fn (Get $get): bool => $get('mode') !== 'property'),
            ]),
            FileUpload::make('receipt')
                ->label(__('waste.fields.receipt'))
                ->visibility('private')
                ->directory('waste-receipts')
                ->acceptedFileTypes(Uploads::documents()),
            Textarea::make('note')
                ->label(__('waste.fields.note'))
                ->rows(2),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{waste_treatment_id: string|null, receipt: string|null, note: string|null}
     */
    private static function attributes(array $data): array
    {
        return [
            'waste_treatment_id' => $data['waste_treatment_id'] ?? null,
            'receipt' => $data['receipt'] ?? null,
            'note' => $data['note'] ?? null,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWasteCollections::route('/'),
        ];
    }
}
