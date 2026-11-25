<?php

namespace App\Filament\Resources\AssetResource\Pages;

use App\Filament\Actions\DisposeAssetAction;
use App\Filament\Actions\ShowInBimAction;
use App\Filament\Concerns\CombinesDetailsWithRelationTabs;
use App\Filament\Resources\AssetResource;
use App\Support\Plugins\QrCodeBridge;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAsset extends ViewRecord
{
    protected static string $resource = AssetResource::class;

    use CombinesDetailsWithRelationTabs;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                EditAction::make(),
                QrCodeBridge::action(),
                ShowInBimAction::make(),
                DisposeAssetAction::make(),
                DeleteAction::make(),
            ]),
        ];
    }
}
