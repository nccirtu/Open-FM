<?php

namespace App\Filament\Fm\Resources;

use App\Enums\TicketCategory;
use App\Enums\TicketEstimateStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketSuspensionReason;
use App\Enums\TicketType;
use App\Filament\Fm\Resources\TicketResource\Pages\CreateTicket;
use App\Filament\Fm\Resources\TicketResource\Pages\EditTicket;
use App\Filament\Fm\Resources\TicketResource\Pages\ListTickets;
use App\Filament\RelationManagers\CostEntriesRelationManager;
use App\Filament\RelationManagers\FolderFilesRelationManager;
use App\Models\Asset;
use App\Models\Property;
use App\Models\Ticket;
use App\Models\User;
use App\Services\EntityFolders;
use App\Support\Plugins\CustomFieldsBridge;
use App\Support\ServicePartner;
use App\Support\Uploads;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use LaBoiteACode\FilamentActivityTimeline\Infolists\ActivityTimelineEntry;
use Slimani\MediaManager\Form\MediaPicker;
use Slimani\MediaManager\Infolists\Components\MediaFileEntry;
use Slimani\MediaManager\Models\File;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav_groups.tickets');
    }

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    public static function getModelLabel(): string
    {
        return __('tickets.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('tickets.models');
    }

    public static function getNavigationLabel(): string
    {
        return __('tickets.navigation');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('tickets.sections.report'))
                ->columns(2)
                ->schema([
                    Select::make('property_id')
                        ->label(__('tickets.fields.property'))
                        ->options(fn (): array => Property::query()
                            ->orderBy('property_number')
                            ->get()
                            ->mapWithKeys(fn (Property $property): array => [
                                $property->id => trim(($property->property_number ?? '').' — '.($property->city ?? '')),
                            ])
                            ->all())
                        ->searchable()
                        ->live()
                        ->required(),
                    Select::make('asset_id')
                        ->label(__('tickets.fields.asset'))
                        ->options(fn (Get $get): array => filled($get('property_id'))
                            ? Asset::query()
                                ->where('property_id', $get('property_id'))
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all()
                            : [])
                        ->searchable()
                        ->nullable(),
                    TextInput::make('title')
                        ->label(__('tickets.fields.title'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Textarea::make('description')
                        ->label(__('tickets.fields.description'))
                        ->rows(4)
                        ->columnSpanFull(),
                    Select::make('type')
                        ->label(__('tickets.fields.type'))
                        ->options(TicketType::class)
                        ->default(TicketType::Fault)
                        ->required()
                        ->native(false),
                    Select::make('category')
                        ->label(__('tickets.fields.category'))
                        ->options(TicketCategory::class)
                        ->required()
                        ->native(false),
                    Select::make('priority')
                        ->label(__('tickets.fields.priority'))
                        ->options(TicketPriority::class)
                        ->default(TicketPriority::P3)
                        ->required()
                        ->native(false),
                    FileUpload::make('photos')
                        ->label(__('tickets.fields.photos'))
                        ->image()
                        ->acceptedFileTypes(Uploads::images())
                        ->multiple()
                        ->directory('ticket-photos')
                        ->columnSpanFull(),
                    MediaPicker::make('files')
                        ->relationship('files')
                        ->visible(fn (): bool => auth()->user()?->can('viewAny', File::class) ?? false)
                        ->directory(fn (?Model $record): ?string => EntityFolders::pathFor($record))
                        ->multiple()
                        ->label(__('documents.media.picker'))
                        ->columnSpanFull(),
                ]),
            ...CustomFieldsBridge::formComponents('ticket'),
        ]);
    }

    /**
     * @return array<Component|Field>
     */
    public static function processingFormComponents(): array
    {
        return [
            Select::make('status')
                ->label(__('tickets.fields.status'))
                ->options(TicketStatus::class)
                ->default(TicketStatus::Reported)
                ->required()
                ->live()
                ->native(false),
            Select::make('suspension_reason')
                ->label(__('tickets.fields.suspension_reason'))
                ->options(TicketSuspensionReason::class)
                ->native(false)
                ->visible(fn (Get $get): bool => self::isWaitingStatus($get('status')))
                ->required(fn (Get $get): bool => self::isWaitingStatus($get('status'))),
            Select::make('assigned_to')
                ->label(__('tickets.fields.assignee'))
                ->options(fn (): array => User::query()
                    ->whereHas('organizations', fn (Builder $query) => $query->whereKey(Filament::getTenant()))
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->all())
                ->searchable()
                ->nullable(),
            Select::make('company_id')
                ->label(__('tickets.fields.company'))
                ->relationship('company', 'name', fn (Builder $query): Builder => ServicePartner::restrict($query, auth()->user(), 'id'))
                ->disabled(fn (): bool => ServicePartner::isExternal(auth()->user()))
                ->searchable()
                ->preload()
                ->nullable(),
            Select::make('contract_id')
                ->label(__('contracts.model'))
                ->relationship('contract', 'name', fn (Builder $query): Builder => ServicePartner::isExternal(auth()->user()) ? $query->whereRaw('1 = 0') : $query)
                ->disabled(fn (): bool => ServicePartner::isExternal(auth()->user()))
                ->searchable()
                ->preload()
                ->nullable(),
            DateTimePicker::make('due_at')
                ->label(__('tickets.fields.due_at'))
                ->native(false)
                ->seconds(false)
                ->nullable(),
            Select::make('estimate_status')
                ->label(__('tickets.fields.estimate_status'))
                ->options(TicketEstimateStatus::class)
                ->native(false)
                ->nullable(),
            TextInput::make('estimate_amount')
                ->label(__('tickets.fields.estimate_amount'))
                ->numeric()
                ->minValue(0)
                ->suffix('€')
                ->nullable(),
            Textarea::make('resolution_notes')
                ->label(__('tickets.fields.resolution_notes'))
                ->rows(3)
                ->columnSpanFull(),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $partnerId = ServicePartner::scopeId(auth()->user());

        if ($partnerId !== null) {
            $query->where('company_id', $partnerId);
        }

        return $query;
    }

    public static function isWaitingStatus(mixed $status): bool
    {
        return $status === TicketStatus::Waiting || $status === TicketStatus::Waiting->value;
    }

    public static function processAction(): Action
    {
        return Action::make('process')
            ->label(__('tickets.process.action'))
            ->icon('heroicon-o-adjustments-horizontal')
            ->modalHeading(__('tickets.process.heading'))
            ->modalSubmitActionLabel(__('tickets.process.submit'))
            ->modalWidth(Width::Large)
            ->visible(fn (): bool => auth()->user()?->can('update_ticket') ?? false)
            ->fillForm(fn (Ticket $record): array => [
                'status' => $record->status,
                'assigned_to' => $record->assigned_to,
                'company_id' => $record->company_id,
                'contract_id' => $record->contract_id,
                'due_at' => $record->due_at,
                'resolution_notes' => $record->resolution_notes,
                'suspension_reason' => $record->suspension_reason,
                'estimate_status' => $record->estimate_status,
                'estimate_amount' => $record->estimate_amount,
            ])
            ->schema(self::processingFormComponents())
            ->action(function (Ticket $record, array $data): void {
                if (ServicePartner::isExternal(auth()->user())) {
                    unset($data['company_id'], $data['contract_id']);
                }

                $record->update($data);
            });
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('tickets.sections.report'))
                ->columns(2)
                ->schema([
                    TextEntry::make('title')
                        ->label(__('tickets.fields.title'))
                        ->prefix(fn (Ticket $record): ?string => filled($record->ticket_number) ? "#{$record->ticket_number} " : null)
                        ->columnSpanFull(),
                    TextEntry::make('type')
                        ->label(__('tickets.fields.type'))
                        ->badge(),
                    TextEntry::make('category')
                        ->label(__('tickets.fields.category'))
                        ->badge(),
                    TextEntry::make('property.property_number')
                        ->label(__('tickets.fields.property')),
                    TextEntry::make('asset.name')
                        ->label(__('tickets.fields.asset'))
                        ->placeholder('—'),
                    TextEntry::make('description')
                        ->label(__('tickets.fields.description'))
                        ->placeholder('—')
                        ->columnSpanFull(),
                    TextEntry::make('reporter.name')
                        ->label(__('tickets.fields.reporter'))
                        ->placeholder('—'),
                    TextEntry::make('created_at')
                        ->label(__('tickets.fields.created_at'))
                        ->dateTime('d.m.Y H:i'),
                ]),
            Section::make(__('tickets.sections.processing'))
                ->columns(2)
                ->schema([
                    TextEntry::make('status')
                        ->label(__('tickets.fields.status'))
                        ->badge(),
                    TextEntry::make('priority')
                        ->label(__('tickets.fields.priority'))
                        ->badge(),
                    TextEntry::make('assignee.name')
                        ->label(__('tickets.fields.assignee'))
                        ->placeholder('—'),
                    TextEntry::make('company.name')
                        ->label(__('tickets.fields.company'))
                        ->placeholder('—'),
                    TextEntry::make('due_at')
                        ->label(__('tickets.fields.due_at'))
                        ->dateTime('d.m.Y H:i')
                        ->placeholder('—'),
                    TextEntry::make('resolved_at')
                        ->label(__('tickets.fields.resolved_at'))
                        ->dateTime('d.m.Y H:i')
                        ->placeholder('—'),
                    TextEntry::make('resolution_notes')
                        ->label(__('tickets.fields.resolution_notes'))
                        ->placeholder('—')
                        ->columnSpanFull(),
                ]),
            Section::make(__('documents.media.picker'))
                ->schema([
                    MediaFileEntry::make('files')
                        ->hiddenLabel(),
                ])
                ->visible(fn (Ticket $record): bool => $record->files()->exists()),
            ...CustomFieldsBridge::infolistComponents('ticket'),
            ActivityTimelineEntry::make('activity')
                ->source('spatie')
                ->heading(__('tickets.activities.title'))
                ->perPage(10)
                ->loadMore(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('unread')
                    ->label('')
                    ->state(fn (Ticket $record): ?string => $record->isUnread() ? __('tickets.new') : null)
                    ->badge()
                    ->color('danger'),
                TextColumn::make('ticket_number')
                    ->label('#')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title')
                    ->label(__('tickets.fields.title'))
                    ->searchable()
                    ->limit(40),
                TextColumn::make('property.property_number')
                    ->label(__('tickets.fields.property'))
                    ->description(fn (Ticket $record): ?string => $record->property?->city)
                    ->sortable(),
                TextColumn::make('category')
                    ->label(__('tickets.fields.category'))
                    ->badge(),
                TextColumn::make('priority')
                    ->label(__('tickets.fields.priority'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('tickets.fields.status'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('resolve_due_at')
                    ->label(__('tickets.fields.sla'))
                    ->dateTime('d.m.Y H:i')
                    ->badge()
                    ->color(fn (Ticket $record): string => $record->slaColor())
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('assignee.name')
                    ->label(__('tickets.fields.assignee'))
                    ->placeholder('—'),
                TextColumn::make('due_at')
                    ->label(__('tickets.fields.due_at'))
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('tickets.fields.created_at'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                ...CustomFieldsBridge::tableColumns('ticket'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label(__('tickets.fields.status'))
                    ->options(TicketStatus::class)
                    ->multiple(),
                SelectFilter::make('priority')
                    ->label(__('tickets.fields.priority'))
                    ->options(TicketPriority::class)
                    ->multiple(),
                Filter::make('open')
                    ->label(__('tickets.filters.open'))
                    ->query(fn (Builder $query): Builder => $query->whereIn('status', TicketStatus::open())),
            ])
            ->recordActions([
                ActionGroup::make([
                    self::processAction(),
                    ViewAction::make(),
                    EditAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            TicketResource\RelationManagers\RepliesRelationManager::class,
            CostEntriesRelationManager::class,
            FolderFilesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTickets::route('/'),
            'create' => CreateTicket::route('/create'),
            'view' => TicketResource\Pages\ViewTicket::route('/{record}'),
            'edit' => EditTicket::route('/{record}/edit'),
        ];
    }
}
