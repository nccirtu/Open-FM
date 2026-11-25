<?php

namespace App\Console\Commands;

use App\Models\Plugin;
use App\Support\Plugins\Installer\PluginInstaller;
use Illuminate\Console\Command;

class PluginsRestore extends Command
{
    protected $signature = 'plugins:restore {--if-missing : Nur ausführen, wenn registrierte Plugins fehlen}';

    protected $description = 'Installiert alle registrierten Plugins erneut, z. B. nach composer install bei einem Core-Update (ohne Queue)';

    public function handle(PluginInstaller $installer): int
    {
        $missing = $installer->missingPlugins();

        if ($this->option('if-missing') && $missing === []) {
            $this->info('Alle registrierten Plugins sind installiert.');

            return self::SUCCESS;
        }

        $installer->dispatchRestore(sync: true);

        $failed = Plugin::query()->whereNotNull('installed_at')->where('status', 'failed')->value('last_error');

        if ($failed !== null) {
            $this->error($failed);

            return self::FAILURE;
        }

        $this->info('Plugins wiederhergestellt.');

        return self::SUCCESS;
    }
}
