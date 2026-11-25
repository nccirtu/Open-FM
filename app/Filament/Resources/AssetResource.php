<?php

namespace App\Filament\Resources;

use App\Enums\AssetCondition;
use App\Enums\AssetServiceStatus;
use App\Filament\Actions\ShowInBimAction;
use App\Filament\Exports\AssetExporter;
use App\Filament\Imports\AssetImporter;
use App\Filament\RelationManagers\AssetMetersRelationManager;
use App\Filament\RelationManagers\ChildAssetsRelationManager;
use App\Filament\RelationManagers\ContractsRelationManager;
use App\Filament\RelationManagers\CostEntriesRelationManager;
use App\Filament\RelationManagers\FolderFilesRelationManager;
use App\Filament\RelationManagers\MaintenanceOrdersRelationManager;
use App\Filament\RelationManagers\MaintenancePlansRelationManager;
use App\Filament\RelationManagers\TicketsRelationManager;
use App\Filament\Resources\AssetResource\Pages\CreateAsset;
use App\Filament\Resources\AssetResource\Pages\EditAsset;
use App\Filament\Resources\AssetResource\Pages\ListAssets;
use App\Filament\Resources\AssetResource\Pages\ViewAsset;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\ClassificationNode;
use App\Models\Property;
use App\Models\StructureNode;
use App\Services\EntityFolders;
use App\Support\Plugins\CustomFieldsBridge;
use App\Support\Plugins\QrCodeBridge;
use App\Support\Uploads;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ImportAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Slimani\MediaManager\Form\MediaPicker;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetResource extends Resource
{
    protected static ?string $model = Asset::class;

    protected static ?int $navigationSort = 20;

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav_groups.objects');
    }

    protected static ?string $navigationLabel = 'Assets';

    protected static ?string $pluralModelLabel = 'Assets';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make()
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make(__('assets.sections.location'))
                            ->columns(2)
                            ->schema([
                                Select::make('property_id')
                                    ->label(__('property.model'))
                                    ->relationship('property', 'property_number')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set) => $set('structure_node_id', null))
                                    ->placeholder(__('app.please_select')),
                                Select::make('structure_node_id')
                                    ->label(__('assets.fields.location'))
                                    ->options(fn (Get $get): array => StructureNode::locationOptions($get('property_id')))
                                    ->searchable()
                                    ->nullable(),
                                Select::make('parent_asset_id')
                                    ->label(__('assets.fields.parent_asset'))
                                    ->options(fn (Get $get, ?Asset $record): array => Asset::query()
                                        ->where('property_id', $get('property_id'))
                                        ->when($record, fn ($query) => $query->whereKeyNot($record->getKey()))
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all())
                                    ->searchable()
                                    ->nullable(),
                            ]),
                        Tab::make(__('assets.tabs.general'))
                            ->columns(2)
                            ->schema([
                                FileUpload::make('image')
                                    ->label(__('assets.fields.image'))
                                    ->image()
                                    ->acceptedFileTypes(Uploads::images())
                                    ->imageEditor()
                                    ->visibility('private')
                                    ->directory(fn (?Model $record): string => EntityFolders::pathFor($record) ?? 'assets')
                                    ->maxSize(4096)
                                    ->columnSpanFull(),
                                TextInput::make('name')
                                    ->label(__('assets.fields.name'))
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('asset_number')
                                    ->label(__('assets.fields.asset_number'))
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->visible(fn (?Asset $record): bool => $record !== null),
                                TextInput::make('manufacturer')
                                    ->label(__('assets.fields.manufacturer'))
                                    ->nullable()
                                    ->maxLength(255),
                                TextInput::make('serial_number')
                                    ->label(__('assets.fields.serial_number'))
                                    ->nullable()
                                    ->maxLength(255),
                                Select::make('classification_root_id')
                                    ->label(__('assets.fields.kategorie'))
                                    ->dehydrated(false)
                                    ->native(false)
                                    ->searchable()
                                    ->live()
                                    ->options(fn (): array => self::classificationOptions(null))
                                    ->afterStateUpdated(fn (Set $set) => $set('classification_group_id', null))
                                    ->visible(fn (): bool => ClassificationNode::query()->whereNull('parent_id')->exists()),
                                Select::make('classification_group_id')
                                    ->label(__('assets.fields.gruppe'))
                                    ->dehydrated(false)
                                    ->native(false)
                                    ->searchable()
                                    ->live()
                                    ->options(fn (Get $get): array => self::classificationOptions($get('classification_root_id')))
                                    ->afterStateUpdated(fn (Set $set) => $set('asset_type_id', null))
                                    ->visible(fn (Get $get): bool => filled($get('classification_root_id'))),
                                Select::make('asset_type_id')
                                    ->label(__('assets.fields.type'))
                                    ->options(fn (Get $get): array => self::assetTypeOptions($get('classification_group_id')))
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->createOptionForm([
                                        TextInput::make('name')->label(__('assets.fields.name'))->required()->unique(table: 'asset_types', column: 'name'),
                                    ])
                                    ->createOptionModalHeading(__('assets.actions.create_asset_type_heading'))
                                    ->required(),
                                Select::make('company_id')
                                    ->label(__('assets.fields.maintenance_partner'))
                                    ->relationship('company', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        TextInput::make('name')->label(__('assets.fields.name'))->required()->unique(table: 'companies', column: 'name'),
                                    ])
                                    ->createOptionModalHeading(__('assets.actions.create_company_heading')),
                                Select::make('inspection_company_id')
                                    ->label(__('assets.fields.inspection_company'))
                                    ->relationship('inspectionCompany', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        TextInput::make('name')->label(__('assets.fields.name'))->required()->unique(table: 'companies', column: 'name'),
                                    ])
                                    ->createOptionModalHeading(__('assets.actions.create_inspection_company_heading')),
                                ...CustomFieldsBridge::formComponents('asset'),
                            ]),
                        Tab::make(__('assets.tabs.maintenance'))
                            ->columns(2)
                            ->schema([
                                DatePicker::make('last_maintenance_at')
                                    ->label(__('assets.fields.last_maintenance'))
                                    ->nullable(),
                                DatePicker::make('next_maintenance_at')
                                    ->label(__('assets.fields.next_maintenance'))
                                    ->nullable(),
                                Select::make('condition')
                                    ->label(__('assets.fields.condition'))
                                    ->options(AssetCondition::class)
                                    ->native(false)
                                    ->nullable(),
                                Select::make('service_status')
                                    ->label(__('assets.fields.service_status'))
                                    ->options(AssetServiceStatus::class)
                                    ->native(false)
                                    ->nullable(),
                                DatePicker::make('installed_at')
                                    ->label(__('assets.fields.installed_at'))
                                    ->native(false)
                                    ->nullable(),
                                DatePicker::make('warranty_until')
                                    ->label(__('assets.fields.warranty_until'))
                                    ->native(false)
                                    ->nullable(),
                                DatePicker::make('decommissioned_at')
                                    ->label(__('assets.fields.decommissioned_at'))
                                    ->native(false)
                                    ->nullable(),
                                TextInput::make('purchase_price')
                                    ->label(__('assets.fields.purchase_price'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->suffix('€')
                                    ->nullable(),
                            ]),
                        Tab::make(__('documents.media.picker'))
                            ->schema([
                                MediaPicker::make('files')
                                    ->relationship('files')
                                    ->directory(fn (?Model $record): ?string => EntityFolders::pathFor($record))
                                    ->multiple()
                                    ->hiddenLabel(),
                            ]),
                    ]),
            ]);
    }

    /**
     * @return array<int, string>
     */
    public static function classificationOptions(?string $parentId): array
    {
        return ClassificationNode::query()
            ->when(
                $parentId === null,
                fn (Builder $query) => $query->whereNull('parent_id'),
                fn (Builder $query) => $query->where('parent_id', $parentId),
            )
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn (ClassificationNode $node): array => [$node->getKey() => $node->label()])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function assetTypeOptions(?string $groupId): array
    {
        $group = $groupId !== null ? ClassificationNode::query()->find($groupId) : null;

        return AssetType::query()
            ->when($group !== null, fn (Builder $query) => $query->whereHas(
                'classifications',
                fn (Builder $relationQuery) => $relationQuery->whereIn('classification_node_id', $group->selfAndDescendantIds()),
            ))
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (AssetType $type): array => [$type->getKey() => $type->displayLabelForCurrentTenant()])
            ->all();
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    ImageEntry::make('image')->label(__('assets.fields.image'))->hiddenLabel()->columnSpanFull(),
                    TextEntry::make('name')->label(__('assets.fields.name'))->weight('bold'),
                    TextEntry::make('asset_number')->label(__('assets.fields.asset_number'))->placeholder('—'),
                    TextEntry::make('assetType.name')->label(__('assets.fields.type'))->badge()->placeholder('—')->formatStateUsing(fn (Asset $record): ?string => $record->assetType?->displayLabelForCurrentTenant()),
                    TextEntry::make('property.property_number')->label(__('property.model'))->url(fn (Asset $record): ?string => $record->property ? PropertyResource::getUrl('view', ['record' => $record->property]) : null),
                    TextEntry::make('manufacturer')->label(__('assets.fields.manufacturer'))->placeholder('—'),
                    TextEntry::make('serial_number')->label(__('assets.fields.serial_number'))->placeholder('—'),
                    TextEntry::make('last_maintenance_at')->label(__('assets.fields.last_maintenance'))->date('d.m.Y')->placeholder('—'),
                    TextEntry::make('next_maintenance_at')->label(__('assets.fields.next_maintenance'))->date('d.m.Y')->placeholder('—'),
                    TextEntry::make('company.name')->label(__('assets.fields.maintenance_partner'))->placeholder('—'),
                    TextEntry::make('inspectionCompany.name')->label(__('assets.fields.inspection_company'))->placeholder('—'),
                ]),
            Section::make(__('assets.sections.location'))
                ->columns(3)
                ->schema([
                    TextEntry::make('structure_node_path')
                        ->label(__('assets.fields.location'))
                        ->state(fn (Asset $record): ?string => $record->structureNode?->fullPath())
                        ->placeholder('—'),
                    TextEntry::make('parentAsset.name')->label(__('assets.fields.parent_asset'))->placeholder('—')->url(fn (Asset $record): ?string => $record->parentAsset ? static::getUrl('view', ['record' => $record->parentAsset]) : null),
                ]),
            Section::make(__('assets.sections.lifecycle'))
                ->columns(3)
                ->collapsible()
                ->schema([
                    TextEntry::make('condition')->label(__('assets.fields.condition'))->badge()->placeholder('—'),
                    TextEntry::make('service_status')->label(__('assets.fields.service_status'))->badge()->placeholder('—'),
                    TextEntry::make('purchase_price')->label(__('assets.fields.purchase_price'))->money('EUR', locale: 'de')->placeholder('—'),
                    TextEntry::make('installed_at')->label(__('assets.fields.installed_at'))->date('d.m.Y')->placeholder('—'),
                    TextEntry::make('warranty_until')->label(__('assets.fields.warranty_until'))->date('d.m.Y')->placeholder('—'),
                    TextEntry::make('decommissioned_at')->label(__('assets.fields.decommissioned_at'))->date('d.m.Y')->placeholder('—'),
                ]),
            ...CustomFieldsBridge::infolistComponents('asset'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label(__('assets.fields.image'))
                    ->circular(),
                TextColumn::make('asset_number')
                    ->label(__('assets.fields.asset_number'))
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('assets.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('property.property_number')
                    ->label(__('property.model'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('structureNode.name')
                    ->label(__('assets.fields.location'))
                    ->description(fn (Asset $record): ?string => $record->structureNode?->parent?->name)
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('condition')
                    ->label(__('assets.fields.condition'))
                    ->badge()
                    ->toggleable(),
                TextColumn::make('service_status')
                    ->label(__('assets.fields.service_status'))
                    ->badge()
                    ->toggleable(),
                TextColumn::make('warranty_until')
                    ->label(__('assets.fields.warranty_until'))
                    ->date('d.m.Y')
                    ->badge()
                    ->color(fn (Asset $record): string => $record->warrantyColor())
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('manufacturer')
                    ->label(__('assets.fields.manufacturer'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('company.name')
                    ->label(__('assets.fields.maintenance_partner'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('inspectionCompany.name')
                    ->label(__('assets.fields.inspection_company'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('serial_number')
                    ->label(__('assets.fields.serial_number'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('last_maintenance_at')
                    ->label(__('assets.fields.last_maintenance'))
                    ->date()
                    ->sortable(),
                TextColumn::make('next_maintenance_at')
                    ->label(__('assets.fields.next_maintenance'))
                    ->date()
                    ->sortable(),
                TextColumn::make('assetType.name')
                    ->label(__('assets.fields.type'))
                    ->formatStateUsing(fn (Asset $record): ?string => $record->assetType?->displayLabelForCurrentTenant())
                    ->searchable()
                    ->sortable(),
                ...CustomFieldsBridge::tableColumns('asset', commonOnly: true),
            ])
            ->filters(static::tableFilters(), layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(4)
            ->persistFiltersInSession()
            ->headerActions([
                ImportAction::make()
                    ->importer(AssetImporter::class)
                    ->options(fn (): array => ['organization_id' => Filament::getTenant()?->getKey()])
                    ->visible(fn (): bool => static::canCreate()),
                static::importTemplateAction(),
                ExportAction::make()
                    ->exporter(AssetExporter::class),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    QrCodeBridge::action(),
                    ShowInBimAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    QrCodeBridge::printLabelsBulkAction(),
                    DeleteBulkAction::make(),
                    ExportBulkAction::make()
                        ->exporter(AssetExporter::class),
                ]),
            ]);
    }

    public static function importTemplateAction(): Action
    {
        return Action::make('importTemplate')
            ->label(__('assets.import.template.action'))
            ->icon('heroicon-o-document-arrow-down')
            ->color('gray')
            ->modalHeading(__('assets.import.template.heading'))
            ->modalDescription(__('assets.import.template.description'))
            ->modalSubmitActionLabel(__('assets.import.template.download'))
            ->schema([
                Select::make('asset_type_ids')
                    ->label(__('assets.import.template.asset_types'))
                    ->options(fn (): array => self::assetTypeOptions(null))
                    ->multiple()
                    ->searchable(),
            ])
            ->action(fn (array $data): StreamedResponse => response()->streamDownload(
                function () use ($data): void {
                    echo AssetImporter::templateCsv($data['asset_type_ids'] ?? []);
                },
                'asset-import-vorlage.csv',
                ['Content-Type' => 'text/csv; charset=UTF-8'],
            ))
            ->visible(fn (): bool => static::canCreate());
    }

    /**
     * @return array<int, Filter|SelectFilter|TernaryFilter>
     */
    public static function tableFilters(): array
    {
        return [
            Filter::make('location')
                ->schema([
                    Select::make('property_id')
                        ->label(__('property.model'))
                        ->options(fn (): array => Property::query()->orderBy('property_number')->pluck('property_number', 'id')->all())
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(fn (Set $set) => $set('structure_node_id', null)),
                    Select::make('structure_node_id')
                        ->label(__('assets.fields.location'))
                        ->options(fn (Get $get): array => StructureNode::locationOptions($get('property_id')))
                        ->searchable()
                        ->visible(fn (Get $get): bool => filled($get('property_id'))),
                    Toggle::make('include_sub_locations')
                        ->label(__('assets.filters.include_sub_locations'))
                        ->default(true)
                        ->visible(fn (Get $get): bool => filled($get('structure_node_id'))),
                ])
                ->query(fn (Builder $query, array $data): Builder => static::applyLocationFilter($query, $data))
                ->indicateUsing(function (array $data): array {
                    $indicators = [];

                    if (filled($data['property_id'] ?? null)) {
                        $indicators[] = Indicator::make(__('property.model').': '.Property::query()->whereKey($data['property_id'])->value('property_number'))
                            ->removeField('property_id');
                    }

                    if (filled($data['structure_node_id'] ?? null)) {
                        $indicators[] = Indicator::make(__('assets.fields.location').': '.StructureNode::query()->find($data['structure_node_id'])?->fullPath())
                            ->removeField('structure_node_id');
                    }

                    return $indicators;
                })
                ->columnSpan(2)
                ->columns(2),
            Filter::make('classification')
                ->schema([
                    Select::make('category_id')
                        ->label(__('assets.fields.kategorie'))
                        ->options(fn (): array => self::classificationOptions(null))
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(fn (Set $set) => $set('group_id', null)),
                    Select::make('group_id')
                        ->label(__('assets.fields.gruppe'))
                        ->options(fn (Get $get): array => self::classificationOptions($get('category_id')))
                        ->searchable()
                        ->visible(fn (Get $get): bool => filled($get('category_id'))),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    $node = static::selectedClassificationNode($data);

                    if ($node === null) {
                        return $query;
                    }

                    return $query->whereHas('assetType.classifications', fn (Builder $classifications) => $classifications
                        ->whereIn('classification_node_id', $node->selfAndDescendantIds()));
                })
                ->indicateUsing(function (array $data): ?string {
                    $node = static::selectedClassificationNode($data);

                    return $node === null ? null : __('assets.filters.classification').': '.$node->label();
                })
                ->visible(fn (): bool => ClassificationNode::query()->whereNull('parent_id')->exists())
                ->columnSpan(2)
                ->columns(2),
            SelectFilter::make('asset_type_id')
                ->label(__('assets.fields.type'))
                ->options(fn (): array => self::assetTypeOptions(null))
                ->multiple()
                ->searchable(),
            SelectFilter::make('condition')
                ->label(__('assets.fields.condition'))
                ->options(AssetCondition::class)
                ->multiple(),
            SelectFilter::make('service_status')
                ->label(__('assets.fields.service_status'))
                ->options(AssetServiceStatus::class)
                ->multiple(),
            SelectFilter::make('manufacturer')
                ->label(__('assets.fields.manufacturer'))
                ->options(fn (): array => Asset::query()->whereNotNull('manufacturer')->where('manufacturer', '!=', '')->distinct()->orderBy('manufacturer')->pluck('manufacturer', 'manufacturer')->all())
                ->multiple()
                ->searchable(),
            SelectFilter::make('company_id')
                ->label(__('assets.fields.maintenance_partner'))
                ->relationship('company', 'name')
                ->multiple()
                ->searchable()
                ->preload(),
            SelectFilter::make('inspection_company_id')
                ->label(__('assets.fields.inspection_company'))
                ->relationship('inspectionCompany', 'name')
                ->multiple()
                ->searchable()
                ->preload(),
            SelectFilter::make('maintenance')
                ->label(__('assets.fields.next_maintenance'))
                ->options([
                    'overdue' => __('assets.filters.maintenance_overdue'),
                    'due_30' => __('assets.filters.due_within', ['days' => 30]),
                    'due_90' => __('assets.filters.due_within', ['days' => 90]),
                    'none' => __('assets.filters.maintenance_none'),
                ])
                ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                    'overdue' => $query->whereDate('next_maintenance_at', '<', today()),
                    'due_30' => $query->whereBetween('next_maintenance_at', [today(), today()->addDays(30)]),
                    'due_90' => $query->whereBetween('next_maintenance_at', [today(), today()->addDays(90)]),
                    'none' => $query->whereNull('next_maintenance_at'),
                    default => $query,
                }),
            SelectFilter::make('warranty')
                ->label(__('assets.fields.warranty_until'))
                ->options([
                    'expired' => __('assets.filters.warranty_expired'),
                    'expiring' => __('assets.filters.warranty_expiring'),
                    'valid' => __('assets.filters.warranty_valid'),
                    'none' => __('assets.filters.not_specified'),
                ])
                ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                    'expired' => $query->whereDate('warranty_until', '<', today()),
                    'expiring' => $query->whereBetween('warranty_until', [today(), today()->addDays(90)]),
                    'valid' => $query->whereDate('warranty_until', '>=', today()),
                    'none' => $query->whereNull('warranty_until'),
                    default => $query,
                }),
            Filter::make('installed_at')
                ->schema([
                    DatePicker::make('installed_from')->label(__('assets.filters.installed_from'))->native(false),
                    DatePicker::make('installed_until')->label(__('assets.filters.installed_until'))->native(false),
                ])
                ->query(fn (Builder $query, array $data): Builder => $query
                    ->when($data['installed_from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('installed_at', '>=', $date))
                    ->when($data['installed_until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('installed_at', '<=', $date)))
                ->indicateUsing(function (array $data): array {
                    $indicators = [];

                    foreach (['installed_from', 'installed_until'] as $field) {
                        if (filled($data[$field] ?? null)) {
                            $indicators[] = Indicator::make(__('assets.filters.'.$field).' '.Carbon::parse($data[$field])->format('d.m.Y'))->removeField($field);
                        }
                    }

                    return $indicators;
                })
                ->columnSpan(2)
                ->columns(2),
            TernaryFilter::make('hierarchy')
                ->placeholder(__('assets.filters.all'))
                ->label(__('assets.filters.hierarchy'))
                ->trueLabel(__('assets.filters.top_level_only'))
                ->falseLabel(__('assets.filters.sub_assets_only'))
                ->queries(
                    true: fn (Builder $query): Builder => $query->whereNull('parent_asset_id'),
                    false: fn (Builder $query): Builder => $query->whereNotNull('parent_asset_id'),
                ),
            TernaryFilter::make('decommissioned')
                ->placeholder(__('assets.filters.all'))
                ->label(__('assets.fields.decommissioned_at'))
                ->trueLabel(__('assets.filters.decommissioned'))
                ->falseLabel(__('assets.filters.active'))
                ->queries(
                    true: fn (Builder $query): Builder => $query->whereNotNull('decommissioned_at'),
                    false: fn (Builder $query): Builder => $query->whereNull('decommissioned_at'),
                ),
            TernaryFilter::make('bim')
                ->placeholder(__('assets.filters.all'))
                ->label(__('assets.filters.bim'))
                ->trueLabel(__('assets.filters.bim_linked'))
                ->falseLabel(__('assets.filters.bim_not_linked'))
                ->queries(
                    true: fn (Builder $query): Builder => $query->whereNotNull('ifc_guid'),
                    false: fn (Builder $query): Builder => $query->whereNull('ifc_guid'),
                ),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function applyLocationFilter(Builder $query, array $data): Builder
    {
        if (filled($data['property_id'] ?? null)) {
            $query->where('property_id', $data['property_id']);
        }

        $node = filled($data['structure_node_id'] ?? null) ? StructureNode::query()->find($data['structure_node_id']) : null;

        if ($node === null) {
            return $query;
        }

        if (! ($data['include_sub_locations'] ?? true)) {
            return $query->where('structure_node_id', $node->getKey());
        }

        return $query->whereIn('structure_node_id', $node->descendantsAndSelf()->pluck('id')->all());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected static function selectedClassificationNode(array $data): ?ClassificationNode
    {
        $nodeId = ($data['group_id'] ?? null) ?: ($data['category_id'] ?? null);

        return filled($nodeId) ? ClassificationNode::query()->find($nodeId) : null;
    }

    public static function getRelations(): array
    {
        return [
            ChildAssetsRelationManager::class,
            TicketsRelationManager::class,
            MaintenancePlansRelationManager::class,
            MaintenanceOrdersRelationManager::class,
            ContractsRelationManager::class,
            CostEntriesRelationManager::class,
            AssetMetersRelationManager::class,
            FolderFilesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAssets::route('/'),
            'create' => CreateAsset::route('/create'),
            'view' => ViewAsset::route('/{record}'),
            'edit' => EditAsset::route('/{record}/edit'),
        ];
    }
}
