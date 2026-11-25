<?php

namespace App\Filament\Fm\Resources;

use App\Enums\MaintenanceOrderStatus;
use App\Enums\TicketCategory;
use App\Enums\TicketType;
use App\Filament\Fm\Resources\MaintenanceOrderResource\Pages\EditMaintenanceOrder;
use App\Filament\Fm\Resources\MaintenanceOrderResource\Pages\ListMaintenanceOrders;
use App\Filament\Fm\Resources\MaintenanceOrderResource\Pages\ViewMaintenanceOrder;
use App\Filament\RelationManagers\CostEntriesRelationManager;
use App\Filament\RelationManagers\FolderFilesRelationManager;
use App\Models\MaintenanceOrder;
use App\Models\Ticket;
use App\Support\Plugins\QrCodeBridge;
use App\Support\ServicePartner;
use App\Support\Uploads;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MaintenanceOrderResource extends Resource
{
    protected static ?string $model = MaintenanceOrder::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?int $navigationSort = 20;

    public static function getNavigationGroup(): ?string
    {
        return __('maintenance.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('maintenance.orders.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('maintenance.orders.models');
    }

    public static function getNavigationLabel(): string
    {
        return __('maintenance.orders.navigation');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    DatePicker::make('due_at')
                        ->label(__('maintenance.fields.due_at'))
                        ->native(false)
                        ->required(),
                    Textarea::make('notes')
                        ->label(__('maintenance.fields.notes'))
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $partnerId = ServicePartner::scopeId(auth()->user());

        if ($partnerId !== null) {
            $query->whereHas('plan', fn (Builder $planQuery) => $planQuery->where('company_id', $partnerId));
        }

        return $query;
    }

    public static function completeAction(): Action
    {
        return Action::make('complete')
            ->label(__('maintenance.orders.complete.action'))
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->modalHeading(__('maintenance.orders.complete.heading'))
            ->modalSubmitActionLabel(__('maintenance.orders.complete.submit'))
            ->modalWidth(Width::Large)
            ->visible(fn (MaintenanceOrder $record): bool => $record->status === MaintenanceOrderStatus::Open
                && (auth()->user()?->can('update_maintenance::order') ?? false))
            ->schema([
                CheckboxList::make('completed_tasks')
                    ->label(__('maintenance.orders.complete.tasks'))
                    ->helperText(__('maintenance.orders.complete.tasks_help'))
                    ->options(fn (MaintenanceOrder $record): array => array_combine($record->tasks ?? [], $record->tasks ?? []))
                    ->visible(fn (MaintenanceOrder $record): bool => filled($record->tasks)),
                FileUpload::make('protocol_files')
                    ->label(__('maintenance.orders.complete.protocol'))
                    ->helperText(__('maintenance.orders.complete.protocol_help'))
                    ->multiple()
                    ->directory('maintenance-protocols')
                    ->acceptedFileTypes(Uploads::documents())
                    ->downloadable(),
                Textarea::make('notes')
                    ->label(__('maintenance.fields.notes'))
                    ->rows(3),
                Toggle::make('defect_found')
                    ->label(__('maintenance.orders.complete.defect_found'))
                    ->helperText(__('maintenance.orders.complete.defect_found_help'))
                    ->live(),
                TextInput::make('defect_title')
                    ->label(__('maintenance.orders.complete.defect_title'))
                    ->visible(fn (Get $get): bool => (bool) $get('defect_found'))
                    ->required(fn (Get $get): bool => (bool) $get('defect_found'))
                    ->maxLength(255),
                Textarea::make('defect_description')
                    ->label(__('maintenance.orders.complete.defect_description'))
                    ->visible(fn (Get $get): bool => (bool) $get('defect_found'))
                    ->rows(3),
            ])
            ->action(function (MaintenanceOrder $record, array $data): void {
                $record->forceFill([
                    'status' => MaintenanceOrderStatus::Completed,
                    'completed_at' => now(),
                    'completed_by' => auth()->id(),
                    'protocol_files' => $data['protocol_files'] ?? [],
                    'notes' => $data['notes'] ?? $record->notes,
                    'completed_tasks' => $data['completed_tasks'] ?? [],
                ])->save();

                $record->plan?->advanceNextDueDate();

                if ($data['defect_found'] ?? false) {
                    Ticket::create([
                        'property_id' => $record->asset?->property_id,
                        'asset_id' => $record->asset_id,
                        'type' => TicketType::Defect,
                        'category' => TicketCategory::Other,
                        'title' => $data['defect_title'],
                        'description' => trim(($data['defect_description'] ?? '')."\n\n".__('maintenance.orders.complete.defect_source', [
                            'plan' => $record->plan?->name ?? '—',
                            'date' => now()->format('d.m.Y'),
                        ])),
                    ]);
                }

                Notification::make()
                    ->title(__('maintenance.orders.complete.done'))
                    ->success()
                    ->send();
            });
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(3)
                ->schema([
                    TextEntry::make('plan.name')->label(__('maintenance.plans.model'))->weight('bold')->placeholder('—'),
                    TextEntry::make('asset.name')->label(__('maintenance.fields.asset'))->placeholder('—'),
                    TextEntry::make('status')->label(__('maintenance.fields.status'))->badge(),
                    TextEntry::make('due_at')->label(__('maintenance.fields.due_at'))->date('d.m.Y')->badge()->color(fn (MaintenanceOrder $record): string => $record->dueColor()),
                    TextEntry::make('completed_at')->label(__('maintenance.fields.completed_at'))->dateTime('d.m.Y H:i')->placeholder('—'),
                    TextEntry::make('completedBy.name')->label(__('maintenance.fields.completed_by'))->placeholder('—'),
                    TextEntry::make('notes')->label(__('maintenance.fields.notes'))->placeholder('—')->columnSpanFull(),
                ]),
            Section::make(__('maintenance.fields.tasks'))
                ->columns(2)
                ->collapsible()
                ->schema([
                    TextEntry::make('tasks')->label(__('maintenance.fields.tasks'))->listWithLineBreaks()->bulleted()->placeholder('—'),
                    TextEntry::make('completed_tasks')->label(__('maintenance.orders.complete.tasks'))->listWithLineBreaks()->bulleted()->placeholder('—'),
                    TextEntry::make('protocol_files')
                        ->label(__('maintenance.orders.complete.protocol'))
                        ->state(fn (MaintenanceOrder $record): array => array_map('basename', $record->protocol_files ?? []))
                        ->listWithLineBreaks()
                        ->bulleted()
                        ->placeholder('—')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('plan.name')
                    ->label(__('maintenance.plans.model'))
                    ->searchable()
                    ->description(fn (MaintenanceOrder $record): ?string => $record->plan?->legal_basis),
                TextColumn::make('asset.name')
                    ->label(__('maintenance.fields.asset'))
                    ->description(fn (MaintenanceOrder $record): ?string => trim(($record->asset?->property?->property_number ?? '').' '.($record->asset?->property?->city ?? ''))),
                TextColumn::make('due_at')
                    ->label(__('maintenance.fields.due_at'))
                    ->date('d.m.Y')
                    ->sortable()
                    ->badge()
                    ->color(fn (MaintenanceOrder $record): string => $record->dueColor()),
                TextColumn::make('status')
                    ->label(__('maintenance.fields.status'))
                    ->badge(),
                TextColumn::make('completed_at')
                    ->label(__('maintenance.fields.completed_at'))
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('—'),
                TextColumn::make('completedBy.name')
                    ->label(__('maintenance.fields.completed_by'))
                    ->placeholder('—'),
            ])
            ->defaultSort('due_at')
            ->filters([
                SelectFilter::make('status')
                    ->label(__('maintenance.fields.status'))
                    ->options(MaintenanceOrderStatus::class)
                    ->multiple(),
                Filter::make('overdue')
                    ->label(__('maintenance.orders.filters.overdue'))
                    ->query(fn (Builder $query): Builder => $query
                        ->where('status', MaintenanceOrderStatus::Open)
                        ->whereDate('due_at', '<', now()->toDateString())),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    self::completeAction(),
                    EditAction::make(),
                    QrCodeBridge::action(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    QrCodeBridge::printLabelsBulkAction(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            CostEntriesRelationManager::class,
            FolderFilesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMaintenanceOrders::route('/'),
            'view' => ViewMaintenanceOrder::route('/{record}'),
            'edit' => EditMaintenanceOrder::route('/{record}/edit'),
        ];
    }
}
