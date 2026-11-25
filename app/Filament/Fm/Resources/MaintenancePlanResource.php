<?php

namespace App\Filament\Fm\Resources;

use App\Enums\MaintenanceIntervalUnit;
use App\Enums\MaintenanceType;
use App\Filament\Fm\Resources\MaintenancePlanResource\Pages\CreateMaintenancePlan;
use App\Filament\Fm\Resources\MaintenancePlanResource\Pages\EditMaintenancePlan;
use App\Filament\Fm\Resources\MaintenancePlanResource\Pages\ListMaintenancePlans;
use App\Filament\Fm\Resources\MaintenancePlanResource\Pages\ViewMaintenancePlan;
use App\Filament\RelationManagers\FolderFilesRelationManager;
use App\Filament\RelationManagers\PlanOrdersRelationManager;
use App\Models\Asset;
use App\Models\MaintenanceOrder;
use App\Models\MaintenancePlan;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MaintenancePlanResource extends Resource
{
    protected static ?string $model = MaintenancePlan::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): ?string
    {
        return __('maintenance.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('maintenance.plans.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('maintenance.plans.models');
    }

    public static function getNavigationLabel(): string
    {
        return __('maintenance.plans.navigation');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    Select::make('asset_id')
                        ->label(__('maintenance.fields.asset'))
                        ->options(fn (): array => Asset::query()
                            ->with('property')
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(fn (Asset $asset): array => [
                                $asset->id => trim($asset->name.' ('.($asset->property?->property_number ?? '—').' '.($asset->property?->city ?? '').')'),
                            ])
                            ->all())
                        ->searchable()
                        ->required(),
                    TextInput::make('name')
                        ->label(__('maintenance.fields.name'))
                        ->placeholder(__('maintenance.fields.name_placeholder'))
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
                        ->placeholder(__('maintenance.fields.legal_basis_placeholder'))
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
                    DatePicker::make('next_due_at')
                        ->label(__('maintenance.fields.next_due_at'))
                        ->native(false)
                        ->required(),
                    TextInput::make('lead_time_days')
                        ->label(__('maintenance.fields.lead_time_days'))
                        ->helperText(__('maintenance.fields.lead_time_days_help'))
                        ->numeric()
                        ->minValue(0)
                        ->default(14)
                        ->required(),
                    Select::make('company_id')
                        ->label(__('tickets.fields.company'))
                        ->relationship('company', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable(),
                    Select::make('inspection_company_id')
                        ->label(__('maintenance.fields.inspection_company'))
                        ->relationship('inspectionCompany', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable(),
                    Select::make('contract_id')
                        ->label(__('contracts.model'))
                        ->relationship('contract', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable(),
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

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(3)
                ->schema([
                    TextEntry::make('name')->label(__('maintenance.fields.name'))->weight('bold'),
                    TextEntry::make('asset.name')->label(__('maintenance.fields.asset'))->placeholder('—'),
                    TextEntry::make('type')->label(__('maintenance.fields.type'))->badge(),
                    TextEntry::make('interval')
                        ->label(__('maintenance.fields.interval'))
                        ->state(fn (MaintenancePlan $record): string => trim($record->interval_value.' '.($record->interval_unit?->getLabel() ?? ''))),
                    TextEntry::make('next_due_at')->label(__('maintenance.fields.next_due_at'))->date('d.m.Y')->placeholder('—'),
                    TextEntry::make('lead_time_days')->label(__('maintenance.fields.lead_time_days'))->placeholder('—'),
                    TextEntry::make('legal_basis')->label(__('maintenance.fields.legal_basis'))->placeholder('—'),
                    TextEntry::make('company.name')->label(__('tickets.fields.company'))->placeholder('—'),
                    TextEntry::make('inspectionCompany.name')->label(__('maintenance.fields.inspection_company'))->placeholder('—'),
                    TextEntry::make('contract.name')->label(__('contracts.model'))->placeholder('—'),
                    IconEntry::make('is_active')->label(__('maintenance.fields.is_active'))->boolean(),
                ]),
            Section::make(__('maintenance.fields.tasks'))
                ->columns(2)
                ->collapsible()
                ->schema([
                    TextEntry::make('tasks')->hiddenLabel()->listWithLineBreaks()->bulleted()->placeholder('—'),
                    TextEntry::make('notes')->label(__('maintenance.fields.notes'))->placeholder('—'),
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
                    ->description(fn (MaintenancePlan $record): ?string => $record->legal_basis),
                TextColumn::make('asset.name')
                    ->label(__('maintenance.fields.asset'))
                    ->description(fn (MaintenancePlan $record): ?string => trim(($record->asset?->property?->property_number ?? '').' '.($record->asset?->property?->city ?? ''))),
                TextColumn::make('type')
                    ->label(__('maintenance.fields.type'))
                    ->badge(),
                TextColumn::make('interval_value')
                    ->label(__('maintenance.fields.interval'))
                    ->formatStateUsing(fn (MaintenancePlan $record): string => $record->interval_value.' '.$record->interval_unit->getLabel()),
                TextColumn::make('next_due_at')
                    ->label(__('maintenance.fields.next_due_at'))
                    ->date('d.m.Y')
                    ->sortable()
                    ->badge()
                    ->color(fn (MaintenancePlan $record): string => match (true) {
                        ! $record->is_active => 'gray',
                        $record->next_due_at->isPast() && ! $record->next_due_at->isToday() => 'danger',
                        $record->next_due_at->lessThanOrEqualTo(now()->addDays(14)) => 'warning',
                        default => 'success',
                    }),
                IconColumn::make('is_active')
                    ->label(__('maintenance.fields.is_active'))
                    ->boolean(),
            ])
            ->defaultSort('next_due_at')
            ->filters([
                SelectFilter::make('type')
                    ->label(__('maintenance.fields.type'))
                    ->options(MaintenanceType::class),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('generateOrder')
                        ->label(__('maintenance.plans.generate_order'))
                        ->icon('heroicon-o-plus-circle')
                        ->visible(fn (): bool => auth()->user()?->can('create_maintenance::order') ?? false)
                        ->action(function (MaintenancePlan $record): void {
                            $order = MaintenanceOrder::firstOrCreateForPlanDueDate($record);

                            Notification::make()
                                ->title($order->wasRecentlyCreated
                                    ? __('maintenance.plans.order_created')
                                    : __('maintenance.plans.order_exists'))
                                ->success()
                                ->send();
                        }),
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PlanOrdersRelationManager::class,
            FolderFilesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMaintenancePlans::route('/'),
            'create' => CreateMaintenancePlan::route('/create'),
            'view' => ViewMaintenancePlan::route('/{record}'),
            'edit' => EditMaintenancePlan::route('/{record}/edit'),
        ];
    }
}
