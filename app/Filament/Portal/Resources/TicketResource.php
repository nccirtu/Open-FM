<?php

namespace App\Filament\Portal\Resources;

use App\Enums\TicketCategory;
use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Filament\Portal\Resources\TicketResource\Pages\CreateTicket;
use App\Filament\Portal\Resources\TicketResource\Pages\ListTickets;
use App\Filament\Portal\Resources\TicketResource\Pages\ViewTicket;
use App\Filament\Portal\Resources\TicketResource\RelationManagers\PublicRepliesRelationManager;
use App\Models\Asset;
use App\Models\Property;
use App\Models\StructureNode;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use App\Support\Portal\TicketStatusHistory;
use App\Support\Uploads;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static ?int $navigationSort = 10;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    public static function getModelLabel(): string
    {
        return __('tickets.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('tickets.portal.navigation');
    }

    public static function getNavigationLabel(): string
    {
        return __('tickets.portal.navigation');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('portal.navigation_group');
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        return $user instanceof User
            ? parent::getEloquentQuery()->visibleInPortal($user)
            : parent::getEloquentQuery()->whereRaw('1 = 0');
    }

    /**
     * @return array<string, string>
     */
    public static function filterableProperties(): array
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        $assignedIds = $user->assignedPropertyIds();

        return Property::query()
            ->where(function (Builder $query) use ($user, $assignedIds): void {
                $query->whereIn('id', Ticket::query()->reportedBy($user)->select('property_id'));

                if ($assignedIds !== null) {
                    $query->orWhereKey($assignedIds);
                }
            })
            ->orderBy('property_number')
            ->pluck('property_number', 'id')
            ->all();
    }

    /**
     * @return Collection<int, Property>
     */
    public static function reportableProperties(): Collection
    {
        $assignedIds = auth()->user()?->assignedPropertyIds();

        return Property::query()
            ->when($assignedIds !== null, fn (Builder $query) => $query->whereKey($assignedIds))
            ->orderBy('property_number')
            ->get();
    }

    public static function confirmAction(): Action
    {
        return Action::make('confirmResolution')
            ->label(__('tickets.portal.confirm.action'))
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->modalHeading(__('tickets.portal.confirm.heading'))
            ->modalSubmitActionLabel(__('tickets.portal.confirm.submit'))
            ->visible(fn (Ticket $record): bool => $record->status === TicketStatus::Resolved
                && $record->reported_by === auth()->id())
            ->schema([
                Select::make('rating')
                    ->label(__('tickets.portal.confirm.rating'))
                    ->options(array_combine(range(1, 5), array_map(fn (int $stars): string => str_repeat('★', $stars).str_repeat('☆', 5 - $stars), range(1, 5))))
                    ->native(false)
                    ->helperText(__('tickets.portal.confirm.rating_help')),
            ])
            ->action(function (Ticket $record, array $data): void {
                $record->update([
                    'status' => TicketStatus::Closed,
                    'rating' => $data['rating'] ?? null,
                ]);
            });
    }

    public static function reopenAction(): Action
    {
        return Action::make('reopen')
            ->label(__('tickets.portal.reopen.action'))
            ->icon('heroicon-o-arrow-path')
            ->color('danger')
            ->modalHeading(__('tickets.portal.reopen.heading'))
            ->modalSubmitActionLabel(__('tickets.portal.reopen.submit'))
            ->visible(fn (Ticket $record): bool => $record->status === TicketStatus::Resolved
                && $record->reported_by === auth()->id())
            ->schema([
                Textarea::make('reason')
                    ->label(__('tickets.portal.reopen.reason'))
                    ->rows(3)
                    ->required(),
            ])
            ->action(function (Ticket $record, array $data): void {
                $record->status = $record->assigned_to !== null ? TicketStatus::InProgress : TicketStatus::Reported;
                $record->resolved_at = null;
                $record->save();

                TicketReply::create([
                    'ticket_id' => $record->getKey(),
                    'message' => __('tickets.portal.reopen.reply_prefix').' '.$data['reason'],
                ]);
            });
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('tickets.sections.report'))
                ->columns(2)
                ->schema([
                    Select::make('property_id')
                        ->label(__('tickets.fields.property'))
                        ->options(fn (): array => self::reportableProperties()
                            ->mapWithKeys(fn (Property $property): array => [
                                $property->id => trim(($property->property_number ?? '').' — '.($property->city ?? '')),
                            ])
                            ->all())
                        ->default(function (): ?string {
                            if (request()->filled('property_id')) {
                                return (string) request('property_id');
                            }

                            $properties = self::reportableProperties();

                            return $properties->count() === 1 ? $properties->first()->getKey() : null;
                        })
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
                        ->default(fn (): ?string => request()->filled('asset_id') ? (string) request('asset_id') : null)
                        ->searchable()
                        ->nullable(),
                    Select::make('structure_node_id')
                        ->label(__('property.structure.location'))
                        ->options(fn (Get $get): array => StructureNode::locationOptions($get('property_id')))
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
                    FileUpload::make('photos')
                        ->label(__('tickets.fields.photos'))
                        ->image()
                        ->acceptedFileTypes(Uploads::images())
                        ->multiple()
                        ->directory('ticket-photos')
                        ->columnSpanFull(),
                ]),
        ]);
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
                    TextEntry::make('status')
                        ->label(__('tickets.fields.status'))
                        ->badge(),
                    TextEntry::make('category')
                        ->label(__('tickets.fields.category'))
                        ->badge(),
                    TextEntry::make('priority')
                        ->label(__('tickets.fields.priority'))
                        ->badge(),
                    TextEntry::make('due_at')
                        ->label(__('tickets.fields.due_at'))
                        ->dateTime('d.m.Y H:i')
                        ->placeholder('—'),
                    TextEntry::make('assignee.name')
                        ->label(__('tickets.fields.assignee'))
                        ->placeholder('—'),
                    TextEntry::make('company.name')
                        ->label(__('tickets.fields.company'))
                        ->placeholder('—'),
                    TextEntry::make('property.property_number')
                        ->label(__('tickets.fields.property')),
                    TextEntry::make('asset.name')
                        ->label(__('tickets.fields.asset'))
                        ->placeholder('—'),
                    TextEntry::make('structure_node_path')
                        ->label(__('property.structure.location'))
                        ->state(fn (Ticket $record): ?string => $record->structureNode?->fullPath())
                        ->placeholder('—'),
                    TextEntry::make('rating')
                        ->label(__('tickets.portal.confirm.rating'))
                        ->formatStateUsing(fn (?int $state): string => $state !== null ? str_repeat('★', $state).str_repeat('☆', 5 - $state) : '—')
                        ->placeholder('—'),
                    TextEntry::make('description')
                        ->label(__('tickets.fields.description'))
                        ->placeholder('—')
                        ->columnSpanFull(),
                    TextEntry::make('created_at')
                        ->label(__('tickets.fields.created_at'))
                        ->dateTime('d.m.Y H:i'),
                    TextEntry::make('resolved_at')
                        ->label(__('tickets.fields.resolved_at'))
                        ->dateTime('d.m.Y H:i')
                        ->placeholder('—'),
                    TextEntry::make('last_activity_at')
                        ->label(__('portal.columns.last_activity'))
                        ->since()
                        ->placeholder('—'),
                ]),
            Section::make(__('portal.history.heading'))
                ->schema([
                    RepeatableEntry::make('status_history')
                        ->hiddenLabel()
                        ->columns(2)
                        ->state(fn (Ticket $record): array => TicketStatusHistory::forTicket($record)
                            ->map(fn (array $change): array => [
                                'at' => $change['at'],
                                'status' => $change['from'] === null
                                    ? $change['to']->getLabel()
                                    : $change['from']->getLabel().' → '.$change['to']->getLabel(),
                            ])
                            ->all())
                        ->schema([
                            TextEntry::make('at')
                                ->hiddenLabel()
                                ->dateTime('d.m.Y H:i'),
                            TextEntry::make('status')
                                ->hiddenLabel(),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['property', 'assignee', 'company']))
            ->columns([
                TextColumn::make('ticket_number')
                    ->label('#')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title')
                    ->label(__('tickets.fields.title'))
                    ->searchable()
                    ->limit(50),
                TextColumn::make('property.property_number')
                    ->label(__('tickets.fields.property')),
                TextColumn::make('category')
                    ->label(__('tickets.fields.category'))
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('priority')
                    ->label(__('tickets.fields.priority'))
                    ->badge(),
                TextColumn::make('status')
                    ->label(__('tickets.fields.status'))
                    ->badge(),
                TextColumn::make('assignee.name')
                    ->label(__('tickets.fields.assignee'))
                    ->description(fn (Ticket $record): ?string => $record->company?->name)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('due_at')
                    ->label(__('tickets.fields.due_at'))
                    ->date('d.m.Y')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('last_activity_at')
                    ->label(__('portal.columns.last_activity'))
                    ->since()
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('tickets.fields.created_at'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('last_activity_at', 'desc')
            ->filters([
                SelectFilter::make('property_id')
                    ->label(__('tickets.fields.property'))
                    ->options(fn (): array => self::filterableProperties()),
                SelectFilter::make('status')
                    ->label(__('tickets.fields.status'))
                    ->options(TicketStatus::class)
                    ->multiple(),
                SelectFilter::make('category')
                    ->label(__('tickets.fields.category'))
                    ->options(TicketCategory::class),
                Filter::make('created_period')
                    ->schema([
                        DatePicker::make('from')->label(__('portal.filters.from')),
                        DatePicker::make('until')->label(__('portal.filters.until')),
                    ])
                    ->columns(2)
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date))),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PublicRepliesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTickets::route('/'),
            'create' => CreateTicket::route('/create'),
            'view' => ViewTicket::route('/{record}'),
        ];
    }
}
