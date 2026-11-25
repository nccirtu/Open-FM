<?php

namespace App\Filament\Fm\Resources;

use App\Enums\MaintenanceIntervalUnit;
use App\Enums\MaintenanceType;
use App\Filament\Fm\Resources\MaintenancePlanTemplateResource\Pages\CreateMaintenancePlanTemplate;
use App\Filament\Fm\Resources\MaintenancePlanTemplateResource\Pages\EditMaintenancePlanTemplate;
use App\Filament\Fm\Resources\MaintenancePlanTemplateResource\Pages\ListMaintenancePlanTemplates;
use App\Models\MaintenancePlanTemplate;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MaintenancePlanTemplateResource extends Resource
{
    protected static ?string $model = MaintenancePlanTemplate::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static ?int $navigationSort = 30;

    public static function getNavigationGroup(): ?string
    {
        return __('maintenance.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('maintenance.templates.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('maintenance.templates.models');
    }

    public static function getNavigationLabel(): string
    {
        return __('maintenance.templates.navigation');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    Select::make('asset_type_id')
                        ->label(__('maintenance.templates.asset_type'))
                        ->relationship('assetType', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    TextInput::make('name')
                        ->label(__('maintenance.fields.name'))
                        ->required()
                        ->maxLength(255),
                    Select::make('type')
                        ->label(__('maintenance.fields.type'))
                        ->options(MaintenanceType::class)
                        ->default(MaintenanceType::Maintenance)
                        ->required()
                        ->native(false),
                    TextInput::make('legal_basis')
                        ->label(__('maintenance.fields.legal_basis'))
                        ->maxLength(255),
                    TextInput::make('interval_value')
                        ->label(__('maintenance.fields.interval_value'))
                        ->numeric()
                        ->minValue(1)
                        ->required(),
                    Select::make('interval_unit')
                        ->label(__('maintenance.fields.interval_unit'))
                        ->options(MaintenanceIntervalUnit::class)
                        ->default(MaintenanceIntervalUnit::Months)
                        ->required()
                        ->native(false),
                    TextInput::make('lead_time_days')
                        ->label(__('maintenance.fields.lead_time_days'))
                        ->numeric()
                        ->minValue(0)
                        ->default(14)
                        ->required(),
                    Toggle::make('is_active')
                        ->label(__('maintenance.fields.is_active'))
                        ->default(true)
                        ->inline(false),
                    Textarea::make('notes')
                        ->label(__('maintenance.fields.notes'))
                        ->rows(3)
                        ->columnSpanFull(),
                    Repeater::make('tasks')
                        ->label(__('maintenance.fields.tasks'))
                        ->helperText(__('maintenance.fields.tasks_help'))
                        ->simple(TextInput::make('task')->required()->maxLength(255))
                        ->addActionLabel(__('maintenance.fields.tasks_add'))
                        ->defaultItems(0)
                        ->columnSpanFull()
                        ->nullable(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('maintenance.fields.name'))
                    ->searchable()
                    ->description(fn (MaintenancePlanTemplate $record): ?string => $record->legal_basis),
                TextColumn::make('assetType.name')
                    ->label(__('maintenance.templates.asset_type'))
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('maintenance.fields.type'))
                    ->badge(),
                TextColumn::make('interval_value')
                    ->label(__('maintenance.fields.interval'))
                    ->formatStateUsing(fn (MaintenancePlanTemplate $record): string => $record->interval_value.' '.$record->interval_unit->getLabel()),
                TextColumn::make('country')
                    ->label(__('maintenance.templates.country'))
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('plans_count')
                    ->label(__('maintenance.templates.plans_count'))
                    ->counts('plans'),
                IconColumn::make('is_active')
                    ->label(__('maintenance.fields.is_active'))
                    ->boolean(),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('asset_type_id')
                    ->label(__('maintenance.templates.asset_type'))
                    ->relationship('assetType', 'name'),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ])
                    ->label(__('tickets.actions.label'))
                    ->icon('heroicon-o-ellipsis-vertical'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMaintenancePlanTemplates::route('/'),
            'create' => CreateMaintenancePlanTemplate::route('/create'),
            'edit' => EditMaintenancePlanTemplate::route('/{record}/edit'),
        ];
    }
}
