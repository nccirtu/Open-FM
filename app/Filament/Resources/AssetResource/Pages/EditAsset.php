<?php

namespace App\Filament\Resources\AssetResource\Pages;

use App\Filament\Concerns\AdoptsMediaFilesIntoFolder;
use App\Filament\Concerns\WithoutRelationManagers;
use App\Filament\Resources\AssetResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAsset extends EditRecord
{
    protected static string $resource = AssetResource::class;

    use AdoptsMediaFilesIntoFolder;
    use WithoutRelationManagers;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make(),
                DeleteAction::make(),
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $node = $this->getRecord()->assetType?->categoryForCurrentTenant();

        $data['classification_root_id'] = $node?->root()->getKey();
        $data['classification_group_id'] = $node?->ancestorGroup()->getKey();

        return $data;
    }
}
