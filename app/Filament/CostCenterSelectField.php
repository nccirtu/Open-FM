<?php

namespace App\Filament;

use App\Models\ClassificationNode;
use Filament\Forms\Components\Select;

class CostCenterSelectField
{
    public static function make(): Select
    {
        return Select::make('cost_center_id')
            ->label(__('projects.costs.cost_center'))
            ->options(fn (): array => ClassificationNode::query()
                ->orderBy('code')
                ->get()
                ->mapWithKeys(fn (ClassificationNode $node): array => [$node->getKey() => $node->label()])
                ->all())
            ->searchable()
            ->native(false)
            ->nullable();
    }
}
