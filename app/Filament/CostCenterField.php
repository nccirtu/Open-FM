<?php

namespace App\Filament;

use App\Models\ClassificationNode;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;

class CostCenterField
{
    public static function make(): Component
    {
        return self::hasClassificationTree()
            ? Select::make('cost_center_id')
                ->label(__('projects.costs.cost_group'))
                ->options(fn (): array => ClassificationNode::query()
                    ->orderBy('code')
                    ->get()
                    ->mapWithKeys(fn (ClassificationNode $node): array => [$node->getKey() => $node->label()])
                    ->all())
                ->searchable()
                ->native(false)
            : TextInput::make('cost_group')
                ->label(__('projects.costs.cost_group'))
                ->maxLength(50)
                ->placeholder(__('projects.costs.cost_group_placeholder'));
    }

    private static function hasClassificationTree(): bool
    {
        return ClassificationNode::query()->exists();
    }
}
