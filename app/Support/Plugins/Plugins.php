<?php

namespace App\Support\Plugins;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Support\Collection<string, PluginDefinition> catalog()
 * @method static PluginDefinition definition(string $slug)
 * @method static bool installed(string $slug)
 * @method static bool enabled(string $slug)
 * @method static bool availableFor(string $slug, ?\App\Models\Organization $organization)
 * @method static bool active(string $slug)
 * @method static string unavailableReason(string $slug)
 * @method static bool canManage(?\App\Models\User $user = null)
 * @method static \App\Models\Plugin|null record(string $slug)
 * @method static \Illuminate\Support\Collection<string, \App\Models\Plugin> records()
 * @method static void flush()
 * @method static void simulateMissing(string ...$slugs)
 */
class Plugins extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PluginManager::class;
    }
}
