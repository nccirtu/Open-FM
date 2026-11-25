<?php

namespace App\Support\Plugins;

use App\Filament\Admin\Pages\ManagePlugins;
use Closure;
use Filament\Navigation\NavigationItem;

final class PluginNavigation
{
    public static function placeholder(string $plugin, Closure $label, ?Closure $group = null, ?int $sort = null): NavigationItem
    {
        return NavigationItem::make($label)
            ->icon(Plugins::definition($plugin)->icon)
            ->group($group)
            ->sort($sort)
            ->badge(fn (): string => __('plugins.badge'), 'gray')
            ->url(fn (): string => ManagePlugins::getUrl(['plugin' => $plugin], panel: 'admin'))
            ->visible(fn (): bool => Plugins::canManage() && ! Plugins::active($plugin));
    }
}
