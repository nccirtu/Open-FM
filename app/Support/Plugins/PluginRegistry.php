<?php

namespace App\Support\Plugins;

use App\Enums\PluginSource;
use App\Enums\PluginStatus;
use App\Models\Plugin;
use Composer\InstalledVersions;

class PluginRegistry
{
    public function __construct(private PluginManager $plugins) {}

    public function sync(): void
    {
        foreach ($this->plugins->catalog() as $definition) {
            if (! $this->plugins->installed($definition->slug)) {
                continue;
            }

            $plugin = Plugin::query()->firstOrNew(['slug' => $definition->slug]);

            $plugin->fill([
                'package' => $definition->package,
                'name' => $plugin->name ?? $definition->name(),
                'version' => InstalledVersions::getPrettyVersion($definition->package),
            ]);

            if (! $plugin->exists) {
                $plugin->fill([
                    'source' => PluginSource::Composer,
                    'status' => PluginStatus::Installed,
                    'is_enabled' => true,
                    'installed_at' => now(),
                ]);
            }

            if ($plugin->isDirty()) {
                $plugin->save();
            }
        }

        $catalog = $this->plugins->catalog();

        Plugin::query()->get()
            ->reject(fn (Plugin $plugin): bool => $catalog->has($plugin->slug) || ! InstalledVersions::isInstalled($plugin->package))
            ->each(fn (Plugin $plugin) => $plugin->update(['version' => InstalledVersions::getPrettyVersion($plugin->package)]));

        $this->plugins->flush();
    }

    public function setEnabled(string $slug, bool $enabled): Plugin
    {
        $plugin = $this->recordFor($slug);
        $plugin->update(['is_enabled' => $enabled]);

        return $plugin;
    }

    /**
     * @param  list<string>  $organizationIds
     */
    public function setOrganizations(string $slug, array $organizationIds): Plugin
    {
        $plugin = $this->recordFor($slug);
        $plugin->organizations()->sync($organizationIds);
        $this->plugins->flush();

        return $plugin;
    }

    protected function recordFor(string $slug): Plugin
    {
        $this->sync();

        return Plugin::query()->where('slug', $slug)->firstOrFail();
    }
}
