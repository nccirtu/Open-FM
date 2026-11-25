<?php

namespace App\Filament\Waste\Clusters\Settings\Resources\WasteTreatmentCategories;

use App\Enums\WasteEsrsCategory;
use App\Filament\Waste\Clusters\Settings\Resources\WasteTreatmentCategories\Pages\ManageWasteTreatmentCategories;
use App\Filament\Waste\Clusters\Settings\SettingsCluster;
use App\Models\WasteTreatmentCategory;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WasteTreatmentCategoryResource extends Resource
{
    protected static ?string $model = WasteTreatmentCategory::class;

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static ?int $navigationSort = 50;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('waste.models.treatment_category.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('waste.models.treatment_category.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('waste.fields.name'))
                    ->required()
                    ->maxLength(255),
                Toggle::make('is_recovery')
                    ->label(__('waste.fields.is_recovery'))
                    ->helperText(__('waste.help.is_recovery'))
                    ->live()
                    ->afterStateUpdated(fn (Set $set, ?bool $state) => $state ? null : $set('counts_as_recycling', false)),
                Toggle::make('counts_as_recycling')
                    ->label(__('waste.fields.counts_as_recycling'))
                    ->helperText(__('waste.help.counts_as_recycling'))
                    ->disabled(fn (Get $get): bool => ! $get('is_recovery')),
                Select::make('esrs_category')
                    ->label(__('waste.fields.esrs_category'))
                    ->helperText(__('waste.help.esrs_category'))
                    ->options(WasteEsrsCategory::class)
                    ->native(false),
                ColorPicker::make('color')
                    ->label(__('waste.fields.color')),
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
                    ->searchable(),
                IconColumn::make('is_recovery')
                    ->label(__('waste.fields.is_recovery'))
                    ->boolean(),
                IconColumn::make('counts_as_recycling')
                    ->label(__('waste.fields.counts_as_recycling'))
                    ->boolean(),
                TextColumn::make('esrs_category')
                    ->label(__('waste.fields.esrs_category'))
                    ->badge()
                    ->color('gray')
                    ->placeholder('—'),
                TextColumn::make('treatments_count')
                    ->label(__('waste.models.treatment.plural'))
                    ->counts('treatments'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageWasteTreatmentCategories::route('/'),
        ];
    }
}
