<?php

namespace App\Filament\Waste\Resources\WasteContainers\RelationManagers;

use App\Filament\Waste\Resources\WastePickups\WastePickupResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PickupsRelationManager extends RelationManager
{
    protected static string $relationship = 'pickups';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('waste.models.pickup.plural');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return WastePickupResource::table($table)
            ->defaultSort('due_on', 'desc');
    }
}
