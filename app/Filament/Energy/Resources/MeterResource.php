<?php

namespace App\Filament\Energy\Resources;

use App\Enums\EnergyMedium;
use App\Enums\MeterKind;
use App\Filament\Energy\Actions\RecordReadingAction;
use App\Filament\Energy\Resources\MeterResource\Pages\CreateMeter;
use App\Filament\Energy\Resources\MeterResource\Pages\EditMeter;
use App\Filament\Energy\Resources\MeterResource\Pages\ListMeters;
use App\Filament\Energy\Resources\MeterResource\Pages\ViewMeter;
use App\Filament\Energy\Resources\MeterResource\RelationManagers\MeasuredAssetsRelationManager;
use App\Filament\Energy\Resources\MeterResource\RelationManagers\ReadingsRelationManager;
use App\Filament\Energy\Resources\MeterResource\RelationManagers\SuppliedNodesRelationManager;
use App\Filament\Energy\Resources\MeterResource\Widgets\MeterConsumersWidget;
use App\Filament\Energy\Resources\MeterResource\Widgets\MeterConsumptionChartWidget;
use App\Filament\Energy\Resources\MeterResource\Widgets\MeterStatsWidget;
use App\Models\Asset;
use App\Models\Meter;
use App\Models\Property;
use App\Models\StructureNode;
use App\Support\Plugins\QrCodeBridge;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MeterResource extends Resource
{
    protected static ?string $model = Meter::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bolt';

    protected static ?int $navigationSort = 10;

    public static function getModelLabel(): string
    {
        return __('energy.meter.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('energy.meter.models');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('energy.sections.master'))
                ->columns(2)
                ->schema([
                    Select::make('property_id')
                        ->label(__('property.model'))
                        ->options(fn (): array => Property::query()->orderBy('property_number')->pluck('property_number', 'id')->all())
                        ->required()
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function (Set $set): void {
                            $set('parent_id', null);
                            $set('structure_node_id', null);
                            $set('asset_id', null);
                        }),
                    TextInput::make('name')
                        ->label(__('energy.fields.name'))
                        ->required()
                        ->maxLength(255),
                    Select::make('medium')
                        ->label(__('energy.fields.medium'))
                        ->options(EnergyMedium::class)
                        ->required()
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(function (Set $set, mixed $state): void {
                            $medium = $state instanceof EnergyMedium ? $state : EnergyMedium::tryFrom((string) $state);
                            $set('unit', $medium?->defaultUnit() ?? 'kWh');
                            $set('parent_id', null);
                        }),
                    Select::make('kind')
                        ->label(__('energy.fields.kind'))
                        ->options(MeterKind::class)
                        ->default(MeterKind::Sub)
                        ->required()
                        ->native(false)
                        ->live(),
                    TextInput::make('number')
                        ->label(__('energy.fields.number'))
                        ->maxLength(255)
                        ->hidden(fn (Get $get): bool => self::isVirtual($get('kind'))),
                    Select::make('parent_id')
                        ->label(__('energy.fields.parent'))
                        ->helperText(__('energy.help.parent'))
                        ->options(fn (Get $get, ?Meter $record): array => self::meterOptions($get('property_id'), $get('medium'), $record))
                        ->searchable()
                        ->nullable(),
                    TextInput::make('unit')
                        ->label(__('energy.fields.unit'))
                        ->default('kWh')
                        ->required()
                        ->maxLength(20)
                        ->datalist(['kWh', 'MWh', 'm³', 'l', 'kg'])
                        ->hidden(fn (Get $get): bool => self::isVirtual($get('kind'))),
                    TextInput::make('factor')
                        ->label(__('energy.fields.factor'))
                        ->helperText(__('energy.help.factor'))
                        ->numeric()
                        ->minValue(0)
                        ->default(1)
                        ->required()
                        ->hidden(fn (Get $get): bool => self::isVirtual($get('kind'))),
                    Select::make('structure_node_id')
                        ->label(__('energy.fields.location'))
                        ->options(fn (Get $get): array => StructureNode::locationOptions($get('property_id')))
                        ->searchable()
                        ->nullable(),
                    Select::make('asset_id')
                        ->label(__('energy.fields.device_asset'))
                        ->helperText(__('energy.help.device_asset'))
                        ->options(fn (Get $get): array => blank($get('property_id')) ? [] : Asset::query()->where('property_id', $get('property_id'))->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->nullable()
                        ->hidden(fn (Get $get): bool => self::isVirtual($get('kind'))),
                    TextInput::make('market_location_id')
                        ->label(__('energy.fields.market_location_id'))
                        ->helperText(__('energy.help.market_location_id'))
                        ->maxLength(50)
                        ->visible(fn (Get $get): bool => self::isMain($get('kind'))),
                    DatePicker::make('calibration_valid_until')
                        ->label(__('energy.fields.calibration_valid_until'))
                        ->native(false)
                        ->hidden(fn (Get $get): bool => self::isVirtual($get('kind'))),
                    Toggle::make('is_active')
                        ->label(__('energy.fields.is_active'))
                        ->default(true),
                    Textarea::make('notes')
                        ->label(__('energy.fields.notes'))
                        ->rows(2)
                        ->columnSpanFull(),
                ]),
            Section::make(__('energy.sections.formula'))
                ->description(__('energy.help.formula'))
                ->visible(fn (Get $get): bool => self::isVirtual($get('kind')))
                ->schema([
                    Repeater::make('formula')
                        ->hiddenLabel()
                        ->columns(4)
                        ->defaultItems(2)
                        ->minItems(1)
                        ->addActionLabel(__('energy.actions.add_formula_term'))
                        ->schema([
                            Select::make('sign')
                                ->label(__('energy.fields.sign'))
                                ->options(['1' => '+', '-1' => '−'])
                                ->default('1')
                                ->required()
                                ->native(false),
                            Select::make('meter_id')
                                ->label(__('energy.meter.model'))
                                ->options(fn (Get $get, ?Meter $record): array => self::meterOptions($get('../../property_id'), $get('../../medium'), $record))
                                ->required()
                                ->searchable()
                                ->columnSpan(3),
                        ]),
                ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('energy.sections.master'))
                ->columnSpanFull()
                ->columns(3)
                ->schema([
                    TextEntry::make('name')->label(__('energy.fields.name'))->weight('bold'),
                    TextEntry::make('number')->label(__('energy.fields.number'))->placeholder('—'),
                    TextEntry::make('property.property_number')->label(__('property.model')),
                    TextEntry::make('medium')->label(__('energy.fields.medium'))->badge(),
                    TextEntry::make('kind')->label(__('energy.fields.kind'))->badge(),
                    TextEntry::make('parent.name')->label(__('energy.fields.parent'))->placeholder('—'),
                    TextEntry::make('unit')
                        ->label(__('energy.fields.unit'))
                        ->formatStateUsing(fn (Meter $record): string => (float) $record->factor === 1.0
                            ? $record->unit
                            : $record->unit.' × '.rtrim(rtrim(number_format((float) $record->factor, 6, ',', '.'), '0'), ',').' = '.$record->evaluationUnit()),
                    TextEntry::make('structure_node_path')
                        ->label(__('energy.fields.location'))
                        ->state(fn (Meter $record): ?string => $record->structureNode?->fullPath())
                        ->placeholder('—'),
                    TextEntry::make('asset.name')->label(__('energy.fields.device_asset'))->placeholder('—'),
                    TextEntry::make('market_location_id')
                        ->label(__('energy.fields.market_location_id'))
                        ->placeholder('—')
                        ->visible(fn (Meter $record): bool => $record->kind === MeterKind::Main || filled($record->market_location_id)),
                    TextEntry::make('calibration_valid_until')
                        ->label(__('energy.fields.calibration_valid_until'))
                        ->date('d.m.Y')
                        ->badge()
                        ->color(fn (Meter $record): string => $record->calibrationColor())
                        ->placeholder('—'),
                    TextEntry::make('formula_text')
                        ->label(__('energy.sections.formula'))
                        ->state(fn (Meter $record): ?string => self::formulaText($record))
                        ->visible(fn (Meter $record): bool => $record->isVirtual()),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $year = now()->year;

        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with(['property', 'parent', 'latestReading', 'measuredAssets'])
                ->withSum(['dailyValues as consumption_year' => fn (Builder $values) => $values->whereYear('date', $year)], 'consumption'))
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label(__('energy.fields.name'))
                    ->description(fn (Meter $record): ?string => $record->number)
                    ->searchable(['name', 'number'])
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('property.property_number')
                    ->label(__('property.model'))
                    ->sortable(),
                TextColumn::make('medium')
                    ->label(__('energy.fields.medium'))
                    ->badge(),
                TextColumn::make('kind')
                    ->label(__('energy.fields.kind'))
                    ->badge()
                    ->toggleable(),
                TextColumn::make('parent.name')
                    ->label(__('energy.fields.parent'))
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('measuredAssets.name')
                    ->label(__('energy.meter.measured_assets'))
                    ->badge()
                    ->color('gray')
                    ->limitList(2)
                    ->expandableLimitedList()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('latestReading.value')
                    ->label(__('energy.fields.last_reading'))
                    ->formatStateUsing(fn (Meter $record): ?string => $record->latestReading ? number_format((float) $record->latestReading->value, 1, ',', '.').' '.$record->unit : null)
                    ->description(fn (Meter $record): ?string => $record->latestReading?->read_at->format('d.m.Y'))
                    ->placeholder('—'),
                TextColumn::make('consumption_year')
                    ->label(__('energy.fields.consumption_year', ['year' => $year]))
                    ->formatStateUsing(fn (Meter $record, mixed $state): string => number_format((float) $state, 0, ',', '.').' '.$record->evaluationUnit())
                    ->alignEnd()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('calibration_valid_until')
                    ->label(__('energy.fields.calibration_valid_until'))
                    ->date('d.m.Y')
                    ->badge()
                    ->color(fn (Meter $record): string => $record->calibrationColor())
                    ->sortable()
                    ->toggleable()
                    ->placeholder('—'),
                IconColumn::make('is_active')
                    ->label(__('energy.fields.is_active'))
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('property_id')
                    ->label(__('property.model'))
                    ->relationship('property', 'property_number')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                SelectFilter::make('medium')
                    ->label(__('energy.fields.medium'))
                    ->options(EnergyMedium::class)
                    ->multiple(),
                SelectFilter::make('kind')
                    ->label(__('energy.fields.kind'))
                    ->options(MeterKind::class),
                TernaryFilter::make('calibration_due')
                    ->label(__('energy.filters.calibration_due'))
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereDate('calibration_valid_until', '<=', now()->addDays(180)),
                        false: fn (Builder $query): Builder => $query->where(fn (Builder $inner) => $inner->whereNull('calibration_valid_until')->orWhereDate('calibration_valid_until', '>', now()->addDays(180))),
                    ),
                TernaryFilter::make('is_active')
                    ->label(__('energy.fields.is_active')),
            ])
            ->recordActions([
                ActionGroup::make([
                    RecordReadingAction::make(),
                    ViewAction::make(),
                    EditAction::make(),
                    QrCodeBridge::action(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    QrCodeBridge::printLabelsBulkAction(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            MeasuredAssetsRelationManager::class,
            ReadingsRelationManager::class,
            SuppliedNodesRelationManager::class,
        ];
    }

    public static function getWidgets(): array
    {
        return [
            MeterStatsWidget::class,
            MeterConsumptionChartWidget::class,
            MeterConsumersWidget::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMeters::route('/'),
            'create' => CreateMeter::route('/create'),
            'view' => ViewMeter::route('/{record}'),
            'edit' => EditMeter::route('/{record}/edit'),
        ];
    }

    public static function isVirtual(mixed $kind): bool
    {
        return $kind === MeterKind::Virtual || $kind === MeterKind::Virtual->value;
    }

    public static function isMain(mixed $kind): bool
    {
        return $kind === MeterKind::Main || $kind === MeterKind::Main->value;
    }

    /**
     * @return array<string, string>
     */
    public static function meterOptions(mixed $propertyId, mixed $medium, ?Meter $except = null): array
    {
        if (blank($propertyId)) {
            return [];
        }

        $medium = $medium instanceof EnergyMedium ? $medium->value : $medium;

        return Meter::query()
            ->where('property_id', $propertyId)
            ->when(filled($medium), fn (Builder $query) => $query->where('medium', $medium))
            ->when($except?->exists, fn (Builder $query) => $query->whereKeyNot($except->getKey()))
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Meter $meter): array => [$meter->getKey() => $meter->name.($meter->number ? ' ('.$meter->number.')' : '')])
            ->all();
    }

    public static function formulaText(Meter $meter): ?string
    {
        $names = Meter::query()->whereKey($meter->formulaMeterIds())->pluck('name', 'id');

        $terms = collect($meter->formula ?? [])
            ->filter(fn (mixed $term): bool => is_array($term) && isset($names[$term['meter_id'] ?? '']))
            ->values()
            ->map(fn (array $term, int $index): string => ((int) ($term['sign'] ?? 1) < 0 ? '− ' : ($index > 0 ? '+ ' : '')).$names[$term['meter_id']]);

        return $terms->isEmpty() ? null : $terms->implode(' ');
    }
}
