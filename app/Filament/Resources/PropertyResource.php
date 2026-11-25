<?php

namespace App\Filament\Resources;

use App\Enums\EnergyRating;
use App\Enums\PropertyUsageType;
use App\Enums\TicketStatus;
use App\Filament\Concerns\HasManagedRecords;
use App\Filament\RelationManagers\ContractsRelationManager;
use App\Filament\RelationManagers\FolderFilesRelationManager;
use App\Filament\RelationManagers\MaintenanceOrdersRelationManager;
use App\Filament\RelationManagers\MaintenancePlansRelationManager;
use App\Filament\RelationManagers\PropertyCostEntriesRelationManager;
use App\Filament\RelationManagers\PropertyMetersRelationManager;
use App\Filament\RelationManagers\TicketsRelationManager;
use App\Filament\Resources\PropertyResource\Pages\BimPage;
use App\Filament\Resources\PropertyResource\Pages\CreateProperty;
use App\Filament\Resources\PropertyResource\Pages\EditProperty;
use App\Filament\Resources\PropertyResource\Pages\ListProperties;
use App\Filament\Resources\PropertyResource\Pages\StructurePage;
use App\Filament\Resources\PropertyResource\Pages\ViewProperty;
use App\Filament\Resources\PropertyResource\RelationManagers\AssetsRelationManager;
use App\Filament\Resources\PropertyResource\RelationManagers\ProjectsRelationManager;
use App\Models\Property;
use App\Services\EntityFolders;
use App\Support\Plugins\CustomFieldsBridge;
use App\Support\Plugins\QrCodeBridge;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;
use Slimani\MediaManager\Form\MediaPicker;

class PropertyResource extends Resource
{
    use HasManagedRecords;

    protected static ?string $model = Property::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home-modern';

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav_groups.objects');
    }

    public static function getModelLabel(): string
    {
        return __('property.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('property.models');
    }

    public static function getNavigationLabel(): string
    {
        return __('property.models');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make()
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make(__('property.sections.master'))
                            ->columns(2)
                            ->schema([
                                TextInput::make('property_number')
                                    ->label(__('property.fields.property_number'))
                                    ->required()
                                    ->unique(
                                        ignoreRecord: true,
                                        modifyRuleUsing: fn (Unique $rule) => $rule->where('organization_id', Filament::getTenant()?->getKey()),
                                    )
                                    ->validationMessages(['unique' => __('property.validation.unique_number')])
                                    ->maxLength(255),
                                static::managerSelect(__('property.fields.manager')),
                                Select::make('usage_type')
                                    ->label(__('property.fields.usage_type'))
                                    ->helperText(__('property.fields.usage_type_help'))
                                    ->options(PropertyUsageType::class)
                                    ->default(PropertyUsageType::Commercial)
                                    ->required()
                                    ->native(false),
                                TextInput::make('street')
                                    ->nullable()
                                    ->maxLength(255)
                                    ->label(__('property.fields.street')),
                                TextInput::make('zip_code')
                                    ->nullable()
                                    ->maxLength(20)
                                    ->label(__('property.fields.zip_code')),
                                TextInput::make('city')
                                    ->nullable()
                                    ->maxLength(100)
                                    ->label(__('property.fields.city')),
                                TextInput::make('state')
                                    ->nullable()
                                    ->maxLength(100)
                                    ->label(__('property.fields.state')),
                                TextInput::make('country')
                                    ->nullable()
                                    ->maxLength(100)
                                    ->label(__('property.fields.country')),
                                TextInput::make('construction_year')
                                    ->label(__('property.fields.construction_year'))
                                    ->helperText(__('property.fields.construction_year_help'))
                                    ->numeric()
                                    ->minValue(1000)
                                    ->maxValue(now()->year + 5),
                            ]),
                        Tab::make(__('property.sections.areas'))
                            ->columns(2)
                            ->schema([
                                TextInput::make('gross_area')
                                    ->label(__('property.fields.gross_area'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->nullable(),
                                TextInput::make('net_area')
                                    ->label(__('property.fields.net_area'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->nullable(),
                                TextInput::make('cleanable_area')
                                    ->label(__('property.fields.cleanable_area'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->nullable(),
                                TextInput::make('heated_volume')
                                    ->label(__('property.fields.heated_volume'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->nullable(),
                            ]),
                        Tab::make(__('property.sections.location_energy'))
                            ->columns(2)
                            ->schema([
                                TextInput::make('latitude')
                                    ->label(__('property.fields.latitude'))
                                    ->numeric()
                                    ->minValue(-90)
                                    ->maxValue(90)
                                    ->nullable(),
                                TextInput::make('longitude')
                                    ->label(__('property.fields.longitude'))
                                    ->numeric()
                                    ->minValue(-180)
                                    ->maxValue(180)
                                    ->nullable(),
                                Select::make('energy_rating')
                                    ->label(__('property.fields.energy_rating'))
                                    ->options(EnergyRating::class)
                                    ->native(false)
                                    ->nullable(),
                                DatePicker::make('energy_cert_valid_until')
                                    ->label(__('property.fields.energy_cert_valid_until'))
                                    ->native(false)
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
                ...CustomFieldsBridge::formComponents('property'),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        $money = fn (TextEntry $entry): TextEntry => $entry->money('EUR', locale: 'de');
        $totals = fn (Property $record): array => $record->costTotals();

        return $schema->components([
            Section::make(__('property.sections.master'))
                ->columns(3)
                ->schema([
                    TextEntry::make('property_number')->label(__('property.fields.property_number'))->weight('bold'),
                    TextEntry::make('manager.name')->label(__('property.fields.manager'))->placeholder('—'),
                    TextEntry::make('usage_type')->label(__('property.fields.usage_type'))->badge(),
                    TextEntry::make('street')->label(__('property.fields.street'))->placeholder('—'),
                    TextEntry::make('zip_code')->label(__('property.fields.zip_code'))->placeholder('—'),
                    TextEntry::make('city')->label(__('property.fields.city'))->placeholder('—'),
                    TextEntry::make('state')->label(__('property.fields.state'))->placeholder('—'),
                    TextEntry::make('country')->label(__('property.fields.country'))->placeholder('—'),
                    TextEntry::make('construction_year')->label(__('property.fields.construction_year'))->placeholder('—'),
                ]),
            Section::make(__('property.sections.overview'))
                ->columns(5)
                ->schema([
                    TextEntry::make('structure_nodes_count')->label(__('property.structure.nodes_count'))->state(fn (Property $record): int => $record->structureNodes()->count()),
                    TextEntry::make('assets_count')->label(__('property.structure.assets_count'))->state(fn (Property $record): int => $record->assets()->count()),
                    TextEntry::make('open_tickets_count')->label(__('property.fields.open_tickets'))->state(fn (Property $record): int => $record->tickets()->whereIn('status', TicketStatus::open())->count())->badge()->color(fn (int $state): string => $state > 0 ? 'warning' : 'gray'),
                    TextEntry::make('projects_count')->label(__('projects.models'))->state(fn (Property $record): int => $record->projects()->count()),
                ]),
            Section::make(__('projects.costs.overview'))
                ->columns(3)
                ->schema([
                    $money(TextEntry::make('costs_total')->label(__('projects.costs.total'))->state(fn (Property $record): float => $totals($record)['total'])->weight('bold')),
                    $money(TextEntry::make('costs_property')->label(__('projects.costs.direct'))->state(fn (Property $record): float => $totals($record)['property'])),
                    $money(TextEntry::make('costs_projects')->label(__('projects.costs.by_projects'))->state(fn (Property $record): float => $totals($record)['projects'])),
                    $money(TextEntry::make('costs_assets')->label(__('projects.costs.by_assets'))->state(fn (Property $record): float => $totals($record)['assets'])),
                    $money(TextEntry::make('costs_tickets')->label(__('projects.costs.by_tickets'))->state(fn (Property $record): float => $totals($record)['tickets'])),
                    $money(TextEntry::make('costs_maintenance')->label(__('projects.costs.by_maintenance'))->state(fn (Property $record): float => $totals($record)['maintenance'])),
                ]),
            Section::make(__('property.sections.areas'))
                ->columns(2)
                ->collapsible()
                ->schema([
                    TextEntry::make('gross_area')->label(__('property.fields.gross_area'))->numeric(decimalPlaces: 2)->placeholder('—'),
                    TextEntry::make('net_area')->label(__('property.fields.net_area'))->numeric(decimalPlaces: 2)->placeholder('—'),
                    TextEntry::make('cleanable_area')->label(__('property.fields.cleanable_area'))->numeric(decimalPlaces: 2)->placeholder('—'),
                    TextEntry::make('heated_volume')->label(__('property.fields.heated_volume'))->numeric(decimalPlaces: 2)->placeholder('—'),
                ]),
            Section::make(__('property.sections.location_energy'))
                ->columns(2)
                ->collapsible()
                ->schema([
                    TextEntry::make('latitude')->label(__('property.fields.latitude'))->placeholder('—'),
                    TextEntry::make('longitude')->label(__('property.fields.longitude'))->placeholder('—'),
                    TextEntry::make('energy_rating')->label(__('property.fields.energy_rating'))->badge()->placeholder('—'),
                    TextEntry::make('energy_cert_valid_until')->label(__('property.fields.energy_cert_valid_until'))->date('d.m.Y')->placeholder('—'),
                ]),
            ...CustomFieldsBridge::infolistComponents('property'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('property_number')
                    ->label(__('property.fields.property_number'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('manager.name')
                    ->label(__('property.fields.manager'))
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('usage_type')
                    ->label(__('property.fields.usage_type'))
                    ->badge()
                    ->toggleable(),
                TextColumn::make('street')
                    ->label(__('property.fields.street'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('zip_code')
                    ->label(__('property.fields.zip_code'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('city')
                    ->label(__('property.fields.city'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('state')
                    ->label(__('property.fields.state'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('country')
                    ->label(__('property.fields.country'))
                    ->searchable()
                    ->sortable(),
                ...CustomFieldsBridge::tableColumns('property'),
            ])
            ->filters([
                SelectFilter::make('usage_type')
                    ->label(__('property.fields.usage_type'))
                    ->options(PropertyUsageType::class),
                SelectFilter::make('manager_id')
                    ->label(__('property.fields.manager'))
                    ->options(fn (): array => static::userOptions())
                    ->searchable(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Action::make('bim')
                        ->label('BIM')
                        ->icon('heroicon-o-cube-transparent')
                        ->url(fn (Property $record): string => static::getUrl('bim', ['record' => $record])),
                    Action::make('dossier')
                        ->label(__('reports.dossier.action'))
                        ->icon('heroicon-o-document-text')
                        ->url(fn (Property $record): string => route('property.dossier.pdf', $record))
                        ->openUrlInNewTab(),
                    QrCodeBridge::action(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    QrCodeBridge::printLabelsBulkAction(),
                    DeleteBulkAction::make()
                        ->authorizeIndividualRecords('delete'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AssetsRelationManager::class,
            ProjectsRelationManager::class,
            TicketsRelationManager::class,
            MaintenancePlansRelationManager::class,
            MaintenanceOrdersRelationManager::class,
            ContractsRelationManager::class,
            PropertyCostEntriesRelationManager::class,
            PropertyMetersRelationManager::class,
            FolderFilesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProperties::route('/'),
            'create' => CreateProperty::route('/create'),
            'view' => ViewProperty::route('/{record}'),
            'edit' => EditProperty::route('/{record}/edit'),
            'bim' => BimPage::route('/{record}/bim'),
            'structure' => StructurePage::route('/{record}/structure'),
        ];
    }
}
