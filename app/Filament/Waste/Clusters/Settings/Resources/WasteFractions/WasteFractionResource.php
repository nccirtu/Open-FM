<?php

namespace App\Filament\Waste\Clusters\Settings\Resources\WasteFractions;

use App\Filament\Waste\Clusters\Settings\Resources\WasteFractions\Pages\ManageWasteFractions;
use App\Filament\Waste\Clusters\Settings\SettingsCluster;
use App\Models\WasteFraction;
use App\Models\WasteTreatment;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class WasteFractionResource extends Resource
{
    protected static ?string $model = WasteFraction::class;

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('waste.models.fraction.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('waste.models.fraction.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label(__('waste.fields.name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('code')
                    ->label(__('waste.fields.waste_code'))
                    ->helperText(__('waste.help.waste_code'))
                    ->maxLength(20),
                Select::make('waste_fraction_group_id')
                    ->label(__('waste.models.fraction_group.singular'))
                    ->relationship('group', 'name', fn ($query) => $query->orderBy('sort'))
                    ->preload()
                    ->searchable(),
                Select::make('waste_treatment_id')
                    ->label(__('waste.fields.default_treatment'))
                    ->relationship('treatment', 'name', fn ($query) => $query->orderBy('sort'))
                    ->getOptionLabelFromRecordUsing(fn (WasteTreatment $record): string => $record->label())
                    ->preload()
                    ->searchable(),
                TextInput::make('density_kg_m3')
                    ->label(__('waste.fields.density'))
                    ->helperText(__('waste.help.density'))
                    ->numeric()
                    ->minValue(0)
                    ->suffix('kg/m³'),
                ColorPicker::make('color')
                    ->label(__('waste.fields.color')),
                Toggle::make('is_hazardous')
                    ->label(__('waste.fields.is_hazardous'))
                    ->helperText(__('waste.help.is_hazardous')),
                Toggle::make('is_active')
                    ->label(__('waste.fields.is_active'))
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                ColorColumn::make('color')
                    ->label(''),
                TextColumn::make('name')
                    ->label(__('waste.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->label(__('waste.fields.waste_code'))
                    ->fontFamily('mono')
                    ->searchable(),
                TextColumn::make('group.name')
                    ->label(__('waste.models.fraction_group.singular'))
                    ->badge()
                    ->color('gray'),
                IconColumn::make('is_hazardous')
                    ->label(__('waste.fields.is_hazardous'))
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->trueColor('danger')
                    ->falseIcon(null),
                TextColumn::make('density_kg_m3')
                    ->label(__('waste.fields.density'))
                    ->numeric(decimalPlaces: 0, locale: 'de')
                    ->suffix(' kg/m³')
                    ->toggleable(),
                TextColumn::make('treatment.code')
                    ->label(__('waste.fields.default_treatment'))
                    ->fontFamily('mono')
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label(__('waste.fields.is_active'))
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('waste_fraction_group_id')
                    ->label(__('waste.models.fraction_group.singular'))
                    ->relationship('group', 'name'),
                TernaryFilter::make('is_hazardous')
                    ->label(__('waste.fields.is_hazardous')),
                TernaryFilter::make('is_active')
                    ->label(__('waste.fields.is_active')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (WasteFraction $record): bool => $record->containers()->exists() || $record->collections()->exists()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageWasteFractions::route('/'),
        ];
    }
}
