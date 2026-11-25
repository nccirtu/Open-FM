<?php

namespace App\Filament\Waste\Clusters\Settings\Resources\WasteContainerTypes;

use App\Filament\Waste\Clusters\Settings\Resources\WasteContainerTypes\Pages\ManageWasteContainerTypes;
use App\Filament\Waste\Clusters\Settings\SettingsCluster;
use App\Models\WasteContainerType;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WasteContainerTypeResource extends Resource
{
    protected static ?string $model = WasteContainerType::class;

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('waste.models.container_type.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('waste.models.container_type.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('waste.fields.name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('default_volume_l')
                    ->label(__('waste.fields.default_volume'))
                    ->numeric()
                    ->minValue(1)
                    ->suffix('l'),
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
                TextColumn::make('default_volume_l')
                    ->label(__('waste.fields.default_volume'))
                    ->numeric(locale: 'de')
                    ->suffix(' l'),
                TextColumn::make('containers_count')
                    ->label(__('waste.models.container.plural'))
                    ->counts('containers'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageWasteContainerTypes::route('/'),
        ];
    }
}
