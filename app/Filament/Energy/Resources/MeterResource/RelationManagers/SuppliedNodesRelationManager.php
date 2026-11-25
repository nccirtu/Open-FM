<?php

namespace App\Filament\Energy\Resources\MeterResource\RelationManagers;

use App\Models\Meter;
use App\Models\StructureNode;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SuppliedNodesRelationManager extends RelationManager
{
    protected static string $relationship = 'suppliedNodes';

    protected static ?string $inverseRelationship = 'supplyingMeters';

    public function isReadOnly(): bool
    {
        return false;
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('energy.meter.supplied_nodes');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([MeasuredAssetsRelationManager::shareInput()]);
    }

    public function table(Table $table): Table
    {
        /** @var Meter $meter */
        $meter = $this->getOwnerRecord();

        return $table
            ->recordTitle(fn (StructureNode $record): string => $record->fullPath())
            ->columns([
                TextColumn::make('name')
                    ->label(__('energy.fields.location'))
                    ->state(fn (StructureNode $record): string => $record->fullPath())
                    ->searchable(['name']),
                TextColumn::make('type')
                    ->label(__('property.structure.type'))
                    ->badge(),
                TextColumn::make('area')
                    ->label(__('property.structure.area'))
                    ->suffix(' m²')
                    ->numeric(decimalPlaces: 1, locale: 'de')
                    ->placeholder('—'),
                TextColumn::make('share')
                    ->label(__('energy.fields.share'))
                    ->suffix(' %')
                    ->numeric(decimalPlaces: 0),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label(__('energy.actions.attach_node'))
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['name'])
                    ->recordSelectOptionsQuery(fn (Builder $query): Builder => $query->where('structure_nodes.property_id', $meter->property_id))
                    ->schema(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        MeasuredAssetsRelationManager::shareInput(),
                    ]),
            ])
            ->recordActions([
                EditAction::make()->label(__('energy.actions.edit_share')),
                DetachAction::make(),
            ]);
    }
}
