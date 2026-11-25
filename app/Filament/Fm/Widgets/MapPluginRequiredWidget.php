<?php

namespace App\Filament\Fm\Widgets;

use App\Support\Plugins\Plugins;
use Filament\Widgets\Widget;

class MapPluginRequiredWidget extends Widget
{
    public static int $gridW = 12;

    public static int $gridH = 5;

    protected string $view = 'filament.widgets.plugin-required';

    public string $plugin = 'map-widget';

    public static function canView(): bool
    {
        return Plugins::canManage() && ! Plugins::active('map-widget');
    }
}
