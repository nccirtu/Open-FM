<?php

namespace App\Filament\Admin\Resources;

use App\Enums\TicketPriority;
use App\Filament\Admin\Resources\SlaDefinitionResource\Pages\CreateSlaDefinition;
use App\Filament\Admin\Resources\SlaDefinitionResource\Pages\EditSlaDefinition;
use App\Filament\Admin\Resources\SlaDefinitionResource\Pages\ListSlaDefinitions;
use App\Models\SlaDefinition;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
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
use Illuminate\Validation\Rules\Unique;

class SlaDefinitionResource extends Resource
{
    protected static ?string $model = SlaDefinition::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): ?string
    {
        return __('app.admin_groups.tickets');
    }

    public static function getModelLabel(): string
    {
        return __('tickets.sla.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('tickets.sla.models');
    }

    public static function getNavigationLabel(): string
    {
        return __('tickets.sla.navigation');
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
                    Select::make('priority')
                        ->label(__('tickets.fields.priority'))
                        ->options(TicketPriority::class)
                        ->required()
                        ->native(false)
                        ->unique(
                            ignoreRecord: true,
                            modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('organization_id', $get('organization_id')),
                        )
                        ->validationMessages(['unique' => __('tickets.sla.priority_unique')]),
                    Toggle::make('is_active')
                        ->label(__('maintenance.fields.is_active'))
                        ->default(true)
                        ->inline(false),
                    TextInput::make('response_hours')
                        ->label(__('tickets.sla.response_hours'))
                        ->helperText(__('tickets.sla.response_hours_help'))
                        ->numeric()
                        ->minValue(1)
                        ->required(),
                    TextInput::make('resolution_hours')
                        ->label(__('tickets.sla.resolution_hours'))
                        ->helperText(__('tickets.sla.resolution_hours_help'))
                        ->numeric()
                        ->minValue(1)
                        ->required(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('organization.name')
                    ->label(__('app.organization'))
                    ->sortable(),
                TextColumn::make('priority')
                    ->label(__('tickets.fields.priority'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('response_hours')
                    ->label(__('tickets.sla.response_hours'))
                    ->suffix(' h'),
                TextColumn::make('resolution_hours')
                    ->label(__('tickets.sla.resolution_hours'))
                    ->suffix(' h'),
                IconColumn::make('is_active')
                    ->label(__('maintenance.fields.is_active'))
                    ->boolean(),
            ])
            ->defaultSort('priority')
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
            'index' => ListSlaDefinitions::route('/'),
            'create' => CreateSlaDefinition::route('/create'),
            'edit' => EditSlaDefinition::route('/{record}/edit'),
        ];
    }
}
