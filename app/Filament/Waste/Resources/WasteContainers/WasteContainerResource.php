<?php

namespace App\Filament\Waste\Resources\WasteContainers;

use App\Enums\StructureNodeType;
use App\Filament\RelationManagers\CostEntriesRelationManager;
use App\Filament\Waste\Pages\CollectionPointsPage;
use App\Filament\Waste\Resources\WasteContainers\Pages\CreateWasteContainer;
use App\Filament\Waste\Resources\WasteContainers\Pages\EditWasteContainer;
use App\Filament\Waste\Resources\WasteContainers\Pages\ListWasteContainers;
use App\Filament\Waste\Resources\WasteContainers\RelationManagers\CollectionsRelationManager;
use App\Filament\Waste\Resources\WasteContainers\RelationManagers\FillReadingsRelationManager;
use App\Filament\Waste\Resources\WasteContainers\RelationManagers\PickupsRelationManager;
use App\Models\Property;
use App\Models\StructureNode;
use App\Models\WasteCollection;
use App\Models\WasteContainer;
use App\Models\WasteContainerType;
use App\Models\WasteFraction;
use App\Models\WastePickup;
use App\Services\Waste\CollectionPoints;
use App\Services\Waste\PickupService;
use App\Support\Plugins\QrCodeBridge;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WasteContainerResource extends Resource
{
    protected static ?string $model = WasteContainer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrash;

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'number';

    public static function getNavigationGroup(): ?string
    {
        return __('waste.navigation.inventory');
    }

    public static function getModelLabel(): string
    {
        return __('waste.models.container.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('waste.models.container.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Select::make('property_id')
                        ->label(__('property.model'))
                        ->relationship('property', 'property_number')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (Set $set) => $set('structure_node_id', null)),
                    Select::make('structure_node_id')
                        ->label(__('waste.fields.location'))
                        ->helperText(__('waste.help.location'))
                        ->options(fn (Get $get): array => StructureNode::locationOptions($get('property_id')))
                        ->searchable()
                        ->createOptionForm(fn (Get $get): array => CollectionPointsPage::formSchema(propertyId: $get('property_id')))
                        ->createOptionAction(fn (Action $action, Get $get): Action => $action
                            ->label(__('waste.collection_points.create'))
                            ->modalHeading(__('waste.collection_points.create'))
                            ->disabled(blank($get('property_id')))
                            ->tooltip(blank($get('property_id')) ? __('waste.collection_points.choose_property') : null))
                        ->createOptionUsing(fn (array $data, Get $get): string => app(CollectionPoints::class)->create(
                            Property::query()->findOrFail($get('property_id')),
                            $data['name'],
                            StructureNodeType::from($data['type']),
                            $data['parent_id'] ?? null,
                        )->getKey()),
                    Select::make('waste_fraction_id')
                        ->label(__('waste.models.fraction.singular'))
                        ->relationship('fraction', 'name', fn (Builder $query) => $query->where('is_active', true)->orderBy('sort'))
                        ->getOptionLabelFromRecordUsing(fn (WasteFraction $record): string => $record->label())
                        ->searchable()
                        ->preload()
                        ->required(),
                    TextInput::make('number')
                        ->label(__('waste.fields.number'))
                        ->required()
                        ->maxLength(50)
                        ->scopedUnique(ignoreRecord: true),
                    Select::make('waste_container_type_id')
                        ->label(__('waste.models.container_type.singular'))
                        ->relationship('type', 'name', fn (Builder $query) => $query->orderBy('sort'))
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function (Set $set, Get $get, ?string $state): void {
                            $volume = filled($state) ? WasteContainerType::query()->whereKey($state)->value('default_volume_l') : null;

                            if ($volume !== null && blank($get('volume_l'))) {
                                $set('volume_l', $volume);
                            }
                        }),
                    TextInput::make('volume_l')
                        ->label(__('waste.fields.volume'))
                        ->numeric()
                        ->minValue(1)
                        ->required()
                        ->suffix('l'),
                    TextInput::make('usual_fill_pct')
                        ->label(__('waste.fields.usual_fill_level'))
                        ->helperText(__('waste.help.usual_fill_level'))
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('%'),
                    Select::make('company_id')
                        ->label(__('waste.fields.company'))
                        ->relationship('company', 'name')
                        ->searchable()
                        ->preload(),
                    Select::make('contract_id')
                        ->label(__('waste.fields.contract'))
                        ->relationship('contract', 'name')
                        ->searchable()
                        ->preload(),
                    Toggle::make('is_active')
                        ->label(__('waste.fields.is_active'))
                        ->default(true)
                        ->inline(false),
                    Textarea::make('notes')
                        ->label(__('waste.fields.notes'))
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
            Section::make(__('waste.sections.prices'))
                ->description(__('waste.help.prices'))
                ->columns(3)
                ->columnSpanFull()
                ->collapsible()
                ->schema([
                    TextInput::make('price_per_pickup')
                        ->label(__('waste.fields.price_per_pickup'))
                        ->numeric()
                        ->minValue(0)
                        ->suffix('€'),
                    TextInput::make('price_per_tonne')
                        ->label(__('waste.fields.price_per_tonne'))
                        ->numeric()
                        ->minValue(0)
                        ->suffix('€/t'),
                    TextInput::make('monthly_rent')
                        ->label(__('waste.fields.monthly_rent'))
                        ->numeric()
                        ->minValue(0)
                        ->suffix('€'),
                ]),
            Section::make(__('waste.sections.position'))
                ->description(__('waste.help.position'))
                ->columns(3)
                ->columnSpanFull()
                ->collapsed()
                ->schema([
                    TextInput::make('latitude')
                        ->label(__('waste.fields.latitude'))
                        ->numeric()
                        ->minValue(-90)
                        ->maxValue(90),
                    TextInput::make('longitude')
                        ->label(__('waste.fields.longitude'))
                        ->numeric()
                        ->minValue(-180)
                        ->maxValue(180),
                    TextInput::make('ifc_guid')
                        ->label(__('waste.fields.ifc_guid'))
                        ->helperText(__('waste.help.ifc_guid'))
                        ->maxLength(64),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['fraction', 'type', 'property', 'structureNode', 'company', 'schedules'])
                ->withMin(['pickups as next_pickup_on' => fn (Builder $pickups) => $pickups
                    ->where('status', 'planned')
                    ->whereDate('due_on', '>=', CarbonImmutable::today()->toDateString())], 'due_on')
                ->addSelect(['last_fill_pct' => WasteCollection::query()
                    ->select('fill_level_pct')
                    ->whereColumn('waste_container_id', 'waste_containers.id')
                    ->whereNotNull('fill_level_pct')
                    ->latest('collected_at')
                    ->limit(1)]))
            ->defaultSort('number')
            ->columns([
                TextColumn::make('number')
                    ->label(__('waste.fields.number'))
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('fraction.name')
                    ->label(__('waste.models.fraction.singular'))
                    ->badge()
                    ->color(fn (WasteContainer $record): array => Color::hex($record->fraction?->displayColor() ?? '#94a3b8'))
                    ->description(fn (WasteContainer $record): ?string => $record->fraction?->code),
                TextColumn::make('volume_l')
                    ->label(__('waste.models.container_type.singular'))
                    ->formatStateUsing(fn (WasteContainer $record): string => number_format($record->volume_l, 0, ',', '.').' l')
                    ->description(fn (WasteContainer $record): ?string => $record->type?->name)
                    ->sortable(),
                TextColumn::make('property.property_number')
                    ->label(__('waste.fields.location'))
                    ->description(fn (WasteContainer $record): ?string => $record->structureNode?->fullPath())
                    ->searchable()
                    ->sortable(),
                TextColumn::make('company.name')
                    ->label(__('waste.fields.company'))
                    ->toggleable(),
                TextColumn::make('rule')
                    ->label(__('waste.fields.rhythm'))
                    ->state(fn (WasteContainer $record): ?string => $record->schedules->first()?->ruleLabel())
                    ->placeholder(__('waste.fields.no_schedule'))
                    ->toggleable(),
                TextColumn::make('next_pickup_on')
                    ->label(__('waste.fields.next_pickup'))
                    ->date('D d.m.')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('last_fill_pct')
                    ->label(__('waste.fields.last_fill_level'))
                    ->suffix(' %')
                    ->badge()
                    ->color(fn (?int $state): string => match (true) {
                        $state === null => 'gray',
                        $state >= 90 => 'danger',
                        $state >= 75 => 'warning',
                        default => 'success',
                    })
                    ->placeholder('—'),
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
                SelectFilter::make('company_id')
                    ->label(__('waste.fields.company'))
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('is_active')
                    ->label(__('waste.fields.is_active'))
                    ->default(true),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    self::requestPickupAction(),
                    QrCodeBridge::action(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    QrCodeBridge::printLabelsBulkAction(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function requestPickupAction(): Action
    {
        return Action::make('requestPickup')
            ->label(__('waste.actions.request_pickup'))
            ->icon('heroicon-o-hand-raised')
            ->color('warning')
            ->requiresConfirmation()
            ->modalDescription(__('waste.help.request_pickup'))
            ->authorize(fn (): bool => auth()->user()?->can('confirm', WastePickup::class) ?? false)
            ->action(function (WasteContainer $record): void {
                app(PickupService::class)->request($record);

                Notification::make()->success()->title(__('waste.notifications.requested'))->send();
            });
    }

    public static function getRelations(): array
    {
        return [
            PickupsRelationManager::class,
            CollectionsRelationManager::class,
            FillReadingsRelationManager::class,
            CostEntriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWasteContainers::route('/'),
            'create' => CreateWasteContainer::route('/create'),
            'edit' => EditWasteContainer::route('/{record}/edit'),
        ];
    }
}
