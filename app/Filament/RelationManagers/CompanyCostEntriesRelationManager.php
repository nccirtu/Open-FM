<?php

namespace App\Filament\RelationManagers;

class CompanyCostEntriesRelationManager extends CostEntriesRelationManager
{
    public function isReadOnly(): bool
    {
        return true;
    }

    protected function showsSource(): bool
    {
        return true;
    }
}
