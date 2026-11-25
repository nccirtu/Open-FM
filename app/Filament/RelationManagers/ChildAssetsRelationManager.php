<?php

namespace App\Filament\RelationManagers;

use App\Filament\Resources\PropertyResource\RelationManagers\AssetsRelationManager;
use Illuminate\Database\Eloquent\Model;

class ChildAssetsRelationManager extends AssetsRelationManager
{
    protected static string $relationship = 'childAssets';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('assets.child_assets');
    }
}
