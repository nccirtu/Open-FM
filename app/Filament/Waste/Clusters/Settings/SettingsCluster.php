<?php

namespace App\Filament\Waste\Clusters\Settings;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class SettingsCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 90;

    public static function getNavigationGroup(): ?string
    {
        return __('waste.navigation.inventory');
    }

    public static function getNavigationLabel(): string
    {
        return __('waste.pages.settings');
    }

    public static function getClusterBreadcrumb(): string
    {
        return __('waste.pages.settings');
    }
}
