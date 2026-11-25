<?php

namespace App\Filament\Concerns;

use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Resources\RelationManagers\RelationManagerConfiguration;

trait WithoutRelationManagers
{
    /**
     * @return array<class-string|RelationGroup|RelationManagerConfiguration>
     */
    public function getRelationManagers(): array
    {
        return [];
    }
}
