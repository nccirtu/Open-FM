<?php

use App\Providers\ActivityTimelineServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\ContractsPanelProvider;
use App\Providers\Filament\EnergyPanelProvider;
use App\Providers\Filament\FmPanelProvider;
use App\Providers\Filament\PmPanelProvider;
use App\Providers\Filament\PortalPanelProvider;
use App\Providers\Filament\WastePanelProvider;

return [
    AppServiceProvider::class,
    ActivityTimelineServiceProvider::class,
    AdminPanelProvider::class,
    ContractsPanelProvider::class,
    EnergyPanelProvider::class,
    FmPanelProvider::class,
    PmPanelProvider::class,
    PortalPanelProvider::class,
    WastePanelProvider::class,
];
