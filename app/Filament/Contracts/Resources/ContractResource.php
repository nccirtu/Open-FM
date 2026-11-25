<?php

namespace App\Filament\Contracts\Resources;

use App\Enums\BillingCycle;
use App\Enums\ContractStatus;
use App\Enums\ContractType;
use App\Filament\Contracts\Resources\ContractResource\Pages\CreateContract;
use App\Filament\Contracts\Resources\ContractResource\Pages\EditContract;
use App\Filament\Contracts\Resources\ContractResource\Pages\ListContracts;
use App\Filament\Contracts\Resources\ContractResource\Pages\ViewContract;
use App\Filament\RelationManagers\CostEntriesRelationManager;
use App\Filament\RelationManagers\FolderFilesRelationManager;
use App\Filament\RelationManagers\MaintenancePlansRelationManager;
use App\Filament\RelationManagers\TicketsRelationManager;
use App\Models\Asset;
use App\Models\Contract;
use App\Models\Property;
use App\Services\EntityFolders;
use App\Support\Plugins\CustomFieldsBridge;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Slimani\MediaManager\Form\MediaPicker;

class ContractResource extends Resource
{
    protected static ?string $model = Contract::class;

    protected static ?int $navigationSort = 10;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    public static function getModelLabel(): string
    {
        return __('contracts.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('contracts.models');
    }

    public static function getNavigationLabel(): string
    {
        return __('contracts.navigation');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('contracts.sections.master_data'))
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label(__('contracts.fields.name'))
                        ->required()
                        ->maxLength(255),
                    TextInput::make('contract_number')
                        ->label(__('contracts.fields.contract_number'))
                        ->maxLength(255),
                    TextInput::make('partner_name')
                        ->label(__('contracts.fields.partner_name'))
                        ->required()
                        ->maxLength(255),
                    Select::make('company_id')
                        ->label(__('tickets.fields.company'))
                        ->relationship('company', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable(),
                    Select::make('type')
                        ->label(__('contracts.fields.type'))
                        ->options(ContractType::class)
                        ->default(ContractType::Service)
                        ->required()
                        ->native(false),
                    Select::make('status')
                        ->label(__('contracts.fields.status'))
                        ->options(ContractStatus::class)
                        ->default(ContractStatus::Active)
                        ->required()
                        ->native(false),
                ]),
            Section::make(__('contracts.sections.term'))
                ->columns(2)
                ->schema([
                    DatePicker::make('start_date')
                        ->label(__('contracts.fields.start_date'))
                        ->native(false),
                    DatePicker::make('end_date')
                        ->label(__('contracts.fields.end_date'))
                        ->helperText(__('contracts.fields.end_date_help'))
                        ->native(false),
                    TextInput::make('notice_period_days')
                        ->label(__('contracts.fields.notice_period_days'))
                        ->helperText(__('contracts.fields.notice_period_days_help'))
                        ->numeric()
                        ->minValue(0),
                    TextInput::make('reminder_lead_days')
                        ->label(__('contracts.fields.reminder_lead_days'))
                        ->helperText(__('contracts.fields.reminder_lead_days_help'))
                        ->numeric()
                        ->minValue(0)
                        ->default(60)
                        ->required(),
                ]),
            Section::make(__('contracts.sections.costs'))
                ->columns(2)
                ->schema([
                    TextInput::make('amount')
                        ->label(__('contracts.fields.amount'))
                        ->numeric()
                        ->minValue(0)
                        ->suffix('€'),
                    Select::make('billing_cycle')
                        ->label(__('contracts.fields.billing_cycle'))
                        ->options(BillingCycle::class)
                        ->native(false)
                        ->nullable(),
                ]),
            Section::make(__('contracts.sections.assignment'))
                ->columns(2)
                ->schema([
                    Select::make('properties')
                        ->label(__('contracts.fields.properties'))
                        ->relationship('properties', 'property_number')
                        ->getOptionLabelFromRecordUsing(fn (Property $property): string => trim($property->property_number.' '.($property->city ?? '')))
                        ->multiple()
                        ->searchable()
                        ->preload(),
                    Select::make('assets')
                        ->label(__('contracts.fields.assets'))
                        ->relationship('assets', 'name')
                        ->getOptionLabelFromRecordUsing(fn (Asset $asset): string => trim($asset->name.' ('.($asset->property?->property_number ?? '—').')'))
                        ->multiple()
                        ->searchable()
                        ->preload(),
                    Textarea::make('notes')
                        ->label(__('contracts.fields.notes'))
                        ->rows(3)
                        ->columnSpanFull(),
                    MediaPicker::make('files')
                        ->relationship('files')
                        ->directory(fn (?Model $record): ?string => EntityFolders::pathFor($record))
                        ->multiple()
                        ->label(__('documents.media.picker'))
                        ->columnSpanFull(),
                ]),
            ...CustomFieldsBridge::formComponents('contract'),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('contracts.sections.master_data'))
                ->columns(3)
                ->schema([
                    TextEntry::make('name')->label(__('contracts.fields.name'))->weight('bold'),
                    TextEntry::make('contract_number')->label(__('contracts.fields.contract_number'))->placeholder('—'),
                    TextEntry::make('partner_name')->label(__('contracts.fields.partner_name'))->placeholder('—'),
                    TextEntry::make('company.name')->label(__('tickets.fields.company'))->placeholder('—'),
                    TextEntry::make('type')->label(__('contracts.fields.type'))->badge(),
                    TextEntry::make('status')->label(__('contracts.fields.status'))->badge(),
                ]),
            Section::make(__('contracts.sections.term'))
                ->columns(3)
                ->schema([
                    TextEntry::make('start_date')->label(__('contracts.fields.start_date'))->date('d.m.Y')->placeholder('—'),
                    TextEntry::make('end_date')->label(__('contracts.fields.end_date'))->date('d.m.Y')->placeholder(__('contracts.fields.open_ended')),
                    TextEntry::make('notice_deadline')
                        ->label(__('contracts.fields.notice_deadline'))
                        ->state(fn (Contract $record): ?string => $record->noticeDeadline()?->format('d.m.Y'))
                        ->badge()
                        ->color(fn (Contract $record): string => $record->noticeDeadlineColor())
                        ->placeholder('—'),
                    TextEntry::make('notice_period_days')->label(__('contracts.fields.notice_period_days'))->placeholder('—'),
                    TextEntry::make('reminder_lead_days')->label(__('contracts.fields.reminder_lead_days'))->placeholder('—'),
                ]),
            Section::make(__('contracts.sections.costs'))
                ->columns(3)
                ->schema([
                    TextEntry::make('amount')->label(__('contracts.fields.amount'))->money('EUR', locale: 'de')->placeholder('—'),
                    TextEntry::make('billing_cycle')->label(__('contracts.fields.billing_cycle'))->badge()->placeholder('—'),
                    TextEntry::make('yearly_amount')->label(__('contracts.stats.yearly_costs'))->state(fn (Contract $record): float => $record->yearlyAmount())->money('EUR', locale: 'de'),
                ]),
            Section::make(__('contracts.sections.assignment'))
                ->columns(2)
                ->collapsible()
                ->schema([
                    TextEntry::make('properties.property_number')->label(__('contracts.fields.properties'))->badge()->placeholder('—'),
                    TextEntry::make('assets.name')->label(__('contracts.fields.assets'))->badge()->placeholder('—'),
                    TextEntry::make('notes')->label(__('contracts.fields.notes'))->placeholder('—')->columnSpanFull(),
                ]),
            ...CustomFieldsBridge::infolistComponents('contract'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('contracts.fields.name'))
                    ->searchable()
                    ->description(fn (Contract $record): ?string => $record->contract_number),
                TextColumn::make('partner_name')
                    ->label(__('contracts.fields.partner_name'))
                    ->searchable(),
                TextColumn::make('type')
                    ->label(__('contracts.fields.type'))
                    ->badge(),
                TextColumn::make('status')
                    ->label(__('contracts.fields.status'))
                    ->badge(),
                TextColumn::make('end_date')
                    ->label(__('contracts.fields.end_date'))
                    ->date('d.m.Y')
                    ->sortable()
                    ->placeholder(__('contracts.fields.open_ended')),
                TextColumn::make('notice_deadline')
                    ->label(__('contracts.fields.notice_deadline'))
                    ->state(fn (Contract $record): ?string => $record->noticeDeadline()?->format('d.m.Y'))
                    ->badge()
                    ->color(fn (Contract $record): string => $record->noticeDeadlineColor())
                    ->placeholder('—'),
                TextColumn::make('amount')
                    ->label(__('contracts.fields.amount'))
                    ->money('EUR')
                    ->sortable()
                    ->description(fn (Contract $record): ?string => $record->billing_cycle?->getLabel()),
                ...CustomFieldsBridge::tableColumns('contract'),
            ])
            ->defaultSort('end_date')
            ->filters([
                SelectFilter::make('type')
                    ->label(__('contracts.fields.type'))
                    ->options(ContractType::class),
                SelectFilter::make('status')
                    ->label(__('contracts.fields.status'))
                    ->options(ContractStatus::class),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            TicketsRelationManager::class,
            MaintenancePlansRelationManager::class,
            CostEntriesRelationManager::class,
            FolderFilesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContracts::route('/'),
            'create' => CreateContract::route('/create'),
            'view' => ViewContract::route('/{record}'),
            'edit' => EditContract::route('/{record}/edit'),
        ];
    }
}
