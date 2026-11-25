<?php

namespace App\Filament\Energy\Resources;

use App\Enums\EnergyGoalPeriod;
use App\Enums\EnergyGoalType;
use App\Enums\EnergyMedium;
use App\Filament\Energy\Resources\EnergyGoalResource\Pages\CreateEnergyGoal;
use App\Filament\Energy\Resources\EnergyGoalResource\Pages\EditEnergyGoal;
use App\Filament\Energy\Resources\EnergyGoalResource\Pages\ListEnergyGoals;
use App\Models\Asset;
use App\Models\EnergyGoal;
use App\Models\Meter;
use App\Models\Property;
use App\Services\Energy\EnergyGoalEvaluator;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\MorphToSelect\Type;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EnergyGoalResource extends Resource
{
    protected static ?string $model = EnergyGoal::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-flag';

    protected static ?int $navigationSort = 30;

    /**
     * @var array<string, array<string, mixed>>
     */
    private static array $evaluations = [];

    public static function getModelLabel(): string
    {
        return __('energy.goal.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('energy.goal.models');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label(__('energy.fields.name'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    MorphToSelect::make('goalable')
                        ->label(__('energy.fields.goal_scope'))
                        ->types([
                            Type::make(Property::class)->titleAttribute('property_number')->label(__('property.model')),
                            Type::make(Meter::class)->titleAttribute('name')->label(__('energy.meter.model')),
                            Type::make(Asset::class)->titleAttribute('name')->label(__('assets.fields.name')),
                        ])
                        ->searchable()
                        ->columnSpanFull(),
                    TextEntry::make('goal_scope_help')
                        ->hiddenLabel()
                        ->state(__('energy.help.goal_scope'))
                        ->color('gray')
                        ->columnSpanFull(),
                    Select::make('medium')
                        ->label(__('energy.fields.medium'))
                        ->options(EnergyMedium::class)
                        ->default(EnergyMedium::Electricity)
                        ->required()
                        ->native(false)
                        ->live(),
                    Select::make('type')
                        ->label(__('energy.fields.goal_type'))
                        ->options(EnergyGoalType::class)
                        ->default(EnergyGoalType::MaxConsumption)
                        ->required()
                        ->native(false)
                        ->live(),
                    Select::make('period')
                        ->label(__('energy.fields.goal_period'))
                        ->options(EnergyGoalPeriod::class)
                        ->default(EnergyGoalPeriod::Year)
                        ->required()
                        ->native(false),
                    TextInput::make('year')
                        ->label(__('energy.fields.year'))
                        ->numeric()
                        ->minValue(2000)
                        ->maxValue(2100)
                        ->default(now()->year)
                        ->required(),
                    TextInput::make('target_value')
                        ->label(__('energy.fields.target_value'))
                        ->numeric()
                        ->minValue(0)
                        ->required()
                        ->suffix(fn (Get $get): string => self::targetUnit($get('type'), $get('medium'))),
                    TextInput::make('baseline_year')
                        ->label(__('energy.fields.baseline_year'))
                        ->numeric()
                        ->minValue(2000)
                        ->maxValue(2100)
                        ->required(fn (Get $get): bool => self::isType($get('type'), EnergyGoalType::Reduction))
                        ->visible(fn (Get $get): bool => self::isType($get('type'), EnergyGoalType::Reduction)),
                    Textarea::make('notes')
                        ->label(__('energy.fields.notes'))
                        ->rows(2)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('goalable'))
            ->defaultSort('year', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label(__('energy.fields.name'))
                    ->description(fn (EnergyGoal $record): string => self::scopeLabel($record))
                    ->searchable()
                    ->weight('medium'),
                TextColumn::make('medium')
                    ->label(__('energy.fields.medium'))
                    ->badge(),
                TextColumn::make('target_value')
                    ->label(__('energy.fields.target_value'))
                    ->formatStateUsing(fn (EnergyGoal $record): string => self::targetText($record))
                    ->description(fn (EnergyGoal $record): string => $record->period->getLabel().' '.$record->year),
                ViewColumn::make('progress')
                    ->label(__('energy.fields.progress'))
                    ->view('filament.energy.goal-progress'),
                TextColumn::make('status')
                    ->label(__('energy.fields.status'))
                    ->badge()
                    ->state(fn (EnergyGoal $record): string => __('energy.goal_status.'.self::evaluation($record)['status']))
                    ->color(fn (EnergyGoal $record): string => self::statusColor(self::evaluation($record)['status'])),
            ])
            ->filters([
                SelectFilter::make('medium')
                    ->label(__('energy.fields.medium'))
                    ->options(EnergyMedium::class),
                SelectFilter::make('year')
                    ->label(__('energy.fields.year'))
                    ->options(fn (): array => EnergyGoal::query()->distinct()->orderByDesc('year')->pluck('year', 'year')->all()),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEnergyGoals::route('/'),
            'create' => CreateEnergyGoal::route('/create'),
            'edit' => EditEnergyGoal::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function evaluation(EnergyGoal $goal): array
    {
        return self::$evaluations[$goal->getKey().'|'.$goal->updated_at] ??= app(EnergyGoalEvaluator::class)->evaluate($goal);
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

    public static function scopeLabel(EnergyGoal $goal): string
    {
        return match (true) {
            $goal->goalable instanceof Property => __('property.model').' '.$goal->goalable->property_number,
            $goal->goalable instanceof Meter => __('energy.meter.model').' '.$goal->goalable->name,
            $goal->goalable instanceof Asset => __('energy.goal.asset').' '.$goal->goalable->name,
            default => __('energy.goal.all_properties'),
        };
    }

    public static function targetText(EnergyGoal $goal): string
    {
        $value = number_format((float) $goal->target_value, $goal->type === EnergyGoalType::MaxIntensity ? 1 : 0, ',', '.');

        return match ($goal->type) {
            EnergyGoalType::Reduction => '−'.$value.' % '.__('energy.goal.vs_year', ['year' => $goal->baseline_year]),
            default => __('energy.goal.max').' '.$value.' '.self::targetUnit($goal->type, $goal->medium),
        };
    }

    public static function targetUnit(mixed $type, mixed $medium): string
    {
        $medium = $medium instanceof EnergyMedium ? $medium : EnergyMedium::tryFrom((string) $medium);
        $unit = $medium?->evaluationUnit() ?? 'kWh';

        return match (true) {
            self::isType($type, EnergyGoalType::Reduction) => '%',
            self::isType($type, EnergyGoalType::MaxIntensity) => $unit.'/m²',
            default => $unit,
        };
    }

    private static function isType(mixed $value, EnergyGoalType $type): bool
    {
        return $value === $type || $value === $type->value;
    }
}
