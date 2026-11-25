<?php

namespace App\Filament\Admin\Resources;

use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Filament\Admin\Resources\TicketAutomationRuleResource\Pages\CreateTicketAutomationRule;
use App\Filament\Admin\Resources\TicketAutomationRuleResource\Pages\EditTicketAutomationRule;
use App\Filament\Admin\Resources\TicketAutomationRuleResource\Pages\ListTicketAutomationRules;
use App\Models\TicketAutomationRule;
use App\Models\User;
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
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TicketAutomationRuleResource extends Resource
{
    protected static ?string $model = TicketAutomationRule::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bolt';

    protected static ?int $navigationSort = 20;

    public static function getNavigationGroup(): ?string
    {
        return __('app.admin_groups.tickets');
    }

    public static function getModelLabel(): string
    {
        return __('tickets.automations.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('tickets.automations.models');
    }

    public static function getNavigationLabel(): string
    {
        return __('tickets.automations.navigation');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    Select::make('organization_id')
                        ->label(__('app.organization'))
                        ->relationship('organization', 'name', modifyQueryUsing: fn (Builder $query) => $query->administeredByCurrentUser())
                        ->required()
                        ->live()
                        ->native(false)
                        ->columnSpanFull(),
                    TextInput::make('name')
                        ->label(__('tickets.automations.name'))
                        ->required()
                        ->maxLength(255),
                    Toggle::make('is_active')
                        ->label(__('tickets.automations.is_active'))
                        ->default(true)
                        ->inline(false),
                    Select::make('event')
                        ->label(__('tickets.automations.event'))
                        ->options(collect(TicketAutomationRule::EVENTS)
                            ->mapWithKeys(fn (string $event): array => [$event => __("tickets.automations.events.{$event}")])
                            ->all())
                        ->required()
                        ->native(false),
                ]),
            Section::make(__('tickets.automations.conditions'))
                ->schema([
                    Repeater::make('conditions')
                        ->hiddenLabel()
                        ->schema([
                            Select::make('field')
                                ->label(__('tickets.automations.condition_field'))
                                ->options(collect(TicketAutomationRule::CONDITION_FIELDS)
                                    ->mapWithKeys(fn (string $field): array => [$field => __("tickets.automations.condition_fields.{$field}")])
                                    ->all())
                                ->live()
                                ->required()
                                ->native(false),
                            Select::make('value')
                                ->label(__('tickets.automations.condition_value'))
                                ->options(fn (Get $get): array => self::enumOptions($get('field')))
                                ->required()
                                ->native(false),
                        ])
                        ->columns(2)
                        ->defaultItems(0),
                ]),
            Section::make(__('tickets.automations.actions'))
                ->schema([
                    Repeater::make('actions')
                        ->hiddenLabel()
                        ->schema([
                            Select::make('type')
                                ->label(__('tickets.automations.action_type'))
                                ->options(collect(TicketAutomationRule::ACTION_TYPES)
                                    ->mapWithKeys(fn (string $type): array => [$type => __("tickets.automations.action_types.{$type}")])
                                    ->all())
                                ->live()
                                ->required()
                                ->native(false),
                            Select::make('value')
                                ->label(__('tickets.automations.action_value'))
                                ->options(fn (Get $get): array => match ($get('type')) {
                                    'assign_to' => User::query()
                                        ->whereHas('organizations', fn (Builder $query) => $query->whereKey($get('organization_id', isAbsolute: true)))
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all(),
                                    'set_status' => self::enumOptions('status'),
                                    'set_priority' => self::enumOptions('priority'),
                                    default => [],
                                })
                                ->visible(fn (Get $get): bool => in_array($get('type'), ['assign_to', 'set_status', 'set_priority'], true))
                                ->required(fn (Get $get): bool => in_array($get('type'), ['assign_to', 'set_status', 'set_priority'], true))
                                ->native(false),
                            Textarea::make('note')
                                ->label(__('tickets.automations.action_note'))
                                ->visible(fn (Get $get): bool => $get('type') === 'add_internal_note')
                                ->required(fn (Get $get): bool => $get('type') === 'add_internal_note')
                                ->rows(2),
                        ])
                        ->columns(2)
                        ->minItems(1)
                        ->required(),
                ]),
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected static function enumOptions(?string $field): array
    {
        $enum = match ($field) {
            'category' => TicketCategory::class,
            'priority' => TicketPriority::class,
            'type' => TicketType::class,
            'status' => TicketStatus::class,
            default => null,
        };

        if ($enum === null) {
            return [];
        }

        return collect($enum::cases())
            ->mapWithKeys(fn ($case): array => [$case->value => $case->getLabel()])
            ->all();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('organization.name')
                    ->label(__('app.organization'))
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('tickets.automations.name'))
                    ->searchable(),
                TextColumn::make('event')
                    ->label(__('tickets.automations.event'))
                    ->formatStateUsing(fn (string $state): string => __("tickets.automations.events.{$state}"))
                    ->badge(),
                IconColumn::make('is_active')
                    ->label(__('tickets.automations.is_active'))
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label(__('tickets.fields.created_at'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('organization_id')->label(__('app.organization'))->relationship('organization', 'name', modifyQueryUsing: fn (Builder $query) => $query->administeredByCurrentUser()),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTicketAutomationRules::route('/'),
            'create' => CreateTicketAutomationRule::route('/create'),
            'edit' => EditTicketAutomationRule::route('/{record}/edit'),
        ];
    }
}
