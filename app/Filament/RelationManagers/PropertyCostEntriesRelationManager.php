<?php

namespace App\Filament\RelationManagers;

use App\Models\CostEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PropertyCostEntriesRelationManager extends CostEntriesRelationManager
{
    /**
     * @return Builder<CostEntry>
     */
    protected function costEntriesQuery(): ?Builder
    {
        return $this->getOwnerRecord()->allCostEntries();
    }

    protected function showsSource(): bool
    {
        return true;
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->allCostEntries()->count();

        return $count > 0 ? (string) $count : null;
    }
}
