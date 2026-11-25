<?php

namespace App\Filament\Waste\Clusters\Settings\Resources\WasteFractionGroups;

use App\Filament\Waste\Clusters\Settings\Resources\WasteFractionGroups\Pages\ManageWasteFractionGroups;
use App\Filament\Waste\Clusters\Settings\SettingsCluster;
use App\Models\WasteFractionGroup;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WasteFractionGroupResource extends Resource
{
    protected static ?string $model = WasteFractionGroup::class;

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('waste.models.fraction_group.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('waste.models.fraction_group.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('waste.fields.name'))
                    ->required()
                    ->maxLength(255),
                Toggle::make('counts_as_separated')
                    ->label(__('waste.fields.counts_as_separated'))
                    ->helperText(__('waste.help.counts_as_separated'))
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('name')
                    ->label(__('waste.fields.name'))
                    ->searchable(),
                IconColumn::make('counts_as_separated')
                    ->label(__('waste.fields.counts_as_separated'))
                    ->boolean(),
                TextColumn::make('fractions_count')
                    ->label(__('waste.models.fraction.plural'))
                    ->counts('fractions'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageWasteFractionGroups::route('/'),
        ];
    }
}
