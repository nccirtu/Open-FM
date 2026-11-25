<?php

namespace App\Flow\Triggers;

use App\Flow\PlaceholderCatalog;
use Packstub\Flow\Nodes\Triggers\RecordCreated as BaseRecordCreated;

class RecordCreated extends BaseRecordCreated
{
    public function getPlaceholders(): array
    {
        return PlaceholderCatalog::all();
    }
}
