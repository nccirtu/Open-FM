<?php

namespace App\Filament\Concerns;

use App\Services\EntityFolders;

trait AdoptsMediaFilesIntoFolder
{
    protected function afterCreate(): void
    {
        EntityFolders::adoptRootFiles($this->getRecord());
    }

    protected function afterSave(): void
    {
        EntityFolders::adoptRootFiles($this->getRecord());
    }
}
