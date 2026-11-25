<?php

namespace App\Filament\RelationManagers;

class PlanOrdersRelationManager extends MaintenanceOrdersRelationManager
{
    protected static string $relationship = 'orders';
}
