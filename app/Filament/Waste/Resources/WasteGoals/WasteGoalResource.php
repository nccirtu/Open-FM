<?php

namespace App\Filament\Waste\Resources\WasteGoals;

use App\Enums\WasteGoalType;
use App\Filament\Waste\Resources\WasteGoals\Pages\ManageWasteGoals;
use App\Models\WasteGoal;
use App\Services\Waste\WasteGoalEvaluator;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WasteGoalResource extends Resource
{
    protected static ?string $model = WasteGoal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static ?int $navigationSort = 50;

    /**
     * @var array<string, array<string, mixed>>
     */
    private static array $evaluations = [];

    public static function getNavigationGroup(): ?string
    {
        return __('waste.navigation.analysis');
    }

    public static function getModelLabel(): string
    {
        return __('waste.models.goal.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('waste.models.goal.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label(__('waste.fields.name'))
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Select::make('property_id')
                    ->label(__('property.model'))
                    ->relationship('property', 'property_number')
                    ->placeholder(__('waste.filters.all_properties'))
                    ->helperText(__('waste.help.goal_property'))
                    ->searchable()
                    ->preload(),
                Select::make('type')
                    ->label(__('waste.fields.goal_type'))
                    ->options(WasteGoalType::class)
                    ->default(WasteGoalType::MinRecyclingRate)
                    ->required()
                    ->native(false)
                    ->live(),
                TextInput::make('year')
                    ->label(__('waste.fields.year'))
                    ->numeric()
                    ->minValue(2000)
                    ->maxValue(2100)
                    ->default(now()->year)
                    ->required(),
                TextInput::make('target_value')
                    ->label(__('waste.fields.target_value'))
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(fn (Get $get): ?int => self::type($get('type'))?->unit() === '%' ? 100 : null)
                    ->required()
                    ->suffix(fn (Get $get): ?string => self::type($get('type'))?->unit()),
                TextInput::make('baseline_year')
                    ->label(__('waste.fields.baseline_year'))
                    ->numeric()
                    ->minValue(2000)
                    ->maxValue(2100)
                    ->required(fn (Get $get): bool => self::type($get('type')) === WasteGoalType::Reduction)
                    ->visible(fn (Get $get): bool => self::type($get('type')) === WasteGoalType::Reduction),
                Textarea::make('notes')
                    ->label(__('waste.fields.notes'))
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('property'))
            ->defaultSort('year', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label(__('waste.fields.name'))
                    ->description(fn (WasteGoal $record): string => self::scopeLabel($record))
                    ->searchable()
                    ->weight('medium'),
                TextColumn::make('type')
                    ->label(__('waste.fields.goal_type'))
                    ->badge()
                    ->color('gray'),
                TextColumn::make('target_value')
                    ->label(__('waste.fields.target_value'))
                    ->formatStateUsing(fn (WasteGoal $record): string => self::targetText($record))
                    ->description(fn (WasteGoal $record): string => (string) $record->year),
                ViewColumn::make('progress')
                    ->label(__('waste.fields.progress'))
                    ->view('filament.waste.goal-progress'),
                TextColumn::make('status')
                    ->label(__('waste.fields.status'))
                    ->badge()
                    ->state(fn (WasteGoal $record): string => __('waste.goal_status.'.self::evaluation($record)['status']))
                    ->color(fn (WasteGoal $record): string => self::statusColor(self::evaluation($record)['status'])),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('waste.fields.goal_type'))
                    ->options(WasteGoalType::class),
                SelectFilter::make('year')
                    ->label(__('waste.fields.year'))
                    ->options(fn (): array => WasteGoal::query()->distinct()->orderByDesc('year')->pluck('year', 'year')->all()),
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
            'index' => ManageWasteGoals::route('/'),
        ];
    }

    /**
     * @return array{actual: float|null, forecast: float|null, target: float|null, status: string, unit: string}
     */
    public static function evaluation(WasteGoal $goal): array
    {
        return self::$evaluations[$goal->getKey().'|'.$goal->updated_at] ??= app(WasteGoalEvaluator::class)->evaluate($goal);
    }

    public static function statusColor(string $status): string
    {
        return match ($status) {
            'good' => 'success',
            'warn' => 'warning',
            'crit' => 'danger',
            default => 'gray',
        };
    }

    public static function scopeLabel(WasteGoal $goal): string
    {
        return $goal->property !== null
            ? __('property.model').' '.$goal->property->property_number
            : __('waste.filters.all_properties');
    }

    public static function targetText(WasteGoal $goal): string
    {
        $value = number_format((float) $goal->target_value, $goal->type === WasteGoalType::MaxMassPerArea ? 1 : 0, ',', '.');

        return match ($goal->type) {
            WasteGoalType::Reduction => '−'.$value.' % '.__('waste.goal.vs_year', ['year' => $goal->baseline_year]),
            WasteGoalType::MinRecyclingRate, WasteGoalType::MinSeparationRate => __('waste.goal.min').' '.$value.' %',
            default => __('waste.goal.max').' '.$value.' '.$goal->type->unit(),
        };
    }

    private static function type(mixed $value): ?WasteGoalType
    {
        return $value instanceof WasteGoalType ? $value : WasteGoalType::tryFrom((string) $value);
    }
}
