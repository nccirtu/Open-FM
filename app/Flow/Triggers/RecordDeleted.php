<?php

namespace App\Flow\Triggers;

use App\Flow\PlaceholderCatalog;
use Packstub\Flow\Nodes\Triggers\RecordDeleted as BaseRecordDeleted;

class RecordDeleted extends BaseRecordDeleted
{
    public function getPlaceholders(): array
    {
        return PlaceholderCatalog::all();
    }
}
