<?php

namespace App\Filament\Waste\Resources\WasteContainers\RelationManagers;

use App\Filament\Waste\Resources\WasteCollections\WasteCollectionResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CollectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'collections';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('waste.models.collection.plural');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return WasteCollectionResource::table($table)
            ->defaultSort('collected_at', 'desc');
    }
}
