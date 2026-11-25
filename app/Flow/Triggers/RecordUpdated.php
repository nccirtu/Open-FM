<?php

namespace App\Flow\Triggers;

use App\Flow\PlaceholderCatalog;
use Packstub\Flow\Nodes\Triggers\RecordUpdated as BaseRecordUpdated;

class RecordUpdated extends BaseRecordUpdated
{
    public function getPlaceholders(): array
    {
        return [
            ...PlaceholderCatalog::all(),
            '{{ original.status }}' => __('packstub-flow::flow.placeholders.original'),
            '{{ changes.status }}' => __('packstub-flow::flow.placeholders.changes'),
        ];
    }
}
