<?php

namespace App\Console\Commands;

use App\Enums\PluginStatus;
use App\Models\Plugin;
use App\Support\Plugins\Installer\PluginInstaller;
use Illuminate\Console\Command;

class PluginsRemove extends Command
{
    protected $signature = 'plugins:remove {plugin : Slug des Plugins}';

    protected $description = 'Entfernt ein Plugin (Composer-Paket); Daten und Lizenz bleiben erhalten (ohne Queue)';

    public function handle(PluginInstaller $installer): int
    {
        $plugin = Plugin::query()->where('slug', $this->argument('plugin'))->first();

        if ($plugin === null) {
            $this->error("Plugin '{$this->argument('plugin')}' ist nicht im Register.");

            return self::FAILURE;
        }

        $installer->dispatch($plugin, 'remove', sync: true);
        $plugin->refresh();

        if ($plugin->status === PluginStatus::Failed) {
            $this->error((string) $plugin->last_error);

            return self::FAILURE;
        }

        $this->info("'{$plugin->package}' ist entfernt.");

        return self::SUCCESS;
    }
}
