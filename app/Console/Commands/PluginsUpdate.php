<?php

namespace App\Console\Commands;

use App\Enums\PluginStatus;
use App\Models\Plugin;
use App\Support\Plugins\Installer\PluginInstaller;
use Illuminate\Console\Command;

class PluginsUpdate extends Command
{
    protected $signature = 'plugins:update {plugin : Slug des Plugins}';

    protected $description = 'Aktualisiert ein installiertes Plugin (ohne Queue)';

    public function handle(PluginInstaller $installer): int
    {
        $plugin = Plugin::query()->where('slug', $this->argument('plugin'))->first();

        if ($plugin === null) {
            $this->error("Plugin '{$this->argument('plugin')}' ist nicht im Register.");

            return self::FAILURE;
        }

        $installer->dispatch($plugin, 'update', sync: true);
        $plugin->refresh();

        if ($plugin->status === PluginStatus::Failed) {
            $this->error((string) $plugin->last_error);

            return self::FAILURE;
        }

        $this->info("'{$plugin->package}' ist aktualisiert ({$plugin->version}).");

        return self::SUCCESS;
    }
}
