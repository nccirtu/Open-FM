<?php

namespace App\Filament\Waste\Clusters\Settings\Resources\WasteTreatments;

use App\Filament\Waste\Clusters\Settings\Resources\WasteTreatments\Pages\ManageWasteTreatments;
use App\Filament\Waste\Clusters\Settings\SettingsCluster;
use App\Models\WasteTreatment;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WasteTreatmentResource extends Resource
{
    protected static ?string $model = WasteTreatment::class;

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPathRoundedSquare;

    protected static ?int $navigationSort = 40;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('waste.models.treatment.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('waste.models.treatment.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label(__('waste.fields.treatment_code'))
                    ->helperText(__('waste.help.treatment_code'))
                    ->maxLength(20),
                TextInput::make('name')
                    ->label(__('waste.fields.name'))
                    ->required()
                    ->maxLength(255),
                Select::make('waste_treatment_category_id')
                    ->label(__('waste.models.treatment_category.singular'))
                    ->relationship('category', 'name', fn ($query) => $query->orderBy('sort'))
                    ->preload()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('code')
                    ->label(__('waste.fields.treatment_code'))
                    ->fontFamily('mono')
                    ->searchable(),
                TextColumn::make('name')
                    ->label(__('waste.fields.name'))
                    ->searchable()
                    ->wrap(),
                TextColumn::make('category.name')
                    ->label(__('waste.models.treatment_category.singular'))
                    ->badge()
                    ->color(fn (WasteTreatment $record): string => $record->category?->is_recovery ? 'success' : 'danger'),
            ])
            ->filters([
                SelectFilter::make('waste_treatment_category_id')
                    ->label(__('waste.models.treatment_category.singular'))
                    ->relationship('category', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageWasteTreatments::route('/'),
        ];
    }
}
