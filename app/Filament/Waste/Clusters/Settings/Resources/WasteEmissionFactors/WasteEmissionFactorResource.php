<?php

namespace App\Filament\Waste\Clusters\Settings\Resources\WasteEmissionFactors;

use App\Filament\Waste\Clusters\Settings\Resources\WasteEmissionFactors\Pages\ManageWasteEmissionFactors;
use App\Filament\Waste\Clusters\Settings\SettingsCluster;
use App\Models\WasteEmissionFactor;
use App\Models\WasteFraction;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WasteEmissionFactorResource extends Resource
{
    protected static ?string $model = WasteEmissionFactor::class;

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCloud;

    protected static ?int $navigationSort = 60;

    public static function getModelLabel(): string
    {
        return __('waste.models.emission_factor.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('waste.models.emission_factor.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('waste_fraction_id')
                    ->label(__('waste.models.fraction.singular'))
                    ->relationship('fraction', 'name', fn (Builder $query) => $query->orderBy('sort'))
                    ->getOptionLabelFromRecordUsing(fn (WasteFraction $record): string => $record->label())
                    ->placeholder(__('waste.filters.all_fractions'))
                    ->searchable()
                    ->preload(),
                Select::make('waste_treatment_category_id')
                    ->label(__('waste.models.treatment_category.singular'))
                    ->relationship('treatmentCategory', 'name', fn (Builder $query) => $query->orderBy('sort'))
                    ->placeholder(__('waste.filters.all_treatment_categories'))
                    ->preload(),
                TextInput::make('kg_co2e_per_t')
                    ->label(__('waste.fields.kg_co2e_per_t'))
                    ->helperText(__('waste.help.kg_co2e_per_t'))
                    ->numeric()
                    ->required()
                    ->suffix('kg CO2e/t'),
                TextInput::make('valid_year')
                    ->label(__('waste.fields.valid_year'))
                    ->helperText(__('waste.help.valid_year'))
                    ->numeric()
                    ->minValue(2000)
                    ->maxValue(2100),
                TextInput::make('source')
                    ->label(__('waste.fields.factor_source'))
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['fraction', 'treatmentCategory']))
            ->defaultSort('valid_year', 'desc')
            ->emptyStateHeading(__('waste.emission.empty'))
            ->emptyStateDescription(__('waste.emission.empty_help'))
            ->columns([
                TextColumn::make('fraction.name')
                    ->label(__('waste.models.fraction.singular'))
                    ->placeholder(__('waste.filters.all_fractions')),
                TextColumn::make('treatmentCategory.name')
                    ->label(__('waste.models.treatment_category.singular'))
                    ->placeholder(__('waste.filters.all_treatment_categories')),
                TextColumn::make('kg_co2e_per_t')
                    ->label(__('waste.fields.kg_co2e_per_t'))
                    ->numeric(decimalPlaces: 1, locale: 'de')
                    ->suffix(' kg/t'),
                TextColumn::make('valid_year')
                    ->label(__('waste.fields.valid_year'))
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('source')
                    ->label(__('waste.fields.factor_source'))
                    ->limit(40)
                    ->tooltip(fn (WasteEmissionFactor $record): ?string => $record->source)
                    ->placeholder('—'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageWasteEmissionFactors::route('/'),
        ];
    }
}
