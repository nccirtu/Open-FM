<?php

namespace App\Observers;

use App\Models\Asset;
use App\Services\MaintenancePlanGenerator;

class AssetObserver
{
    public function created(Asset $asset): void
    {
        app(MaintenancePlanGenerator::class)->forAsset($asset);
    }

    public function updated(Asset $asset): void
    {
        if ($asset->wasChanged('asset_type_id')) {
            app(MaintenancePlanGenerator::class)->forAsset($asset);
        }
    }
}
