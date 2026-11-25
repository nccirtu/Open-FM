<?php

namespace App\Console\Commands;

use App\Support\Plugins\PluginRegistry;
use Illuminate\Console\Command;

class PluginsSync extends Command
{
    protected $signature = 'plugins:sync';

    protected $description = 'Gleicht das Plugin-Register mit den installierten Composer-Paketen ab';

    public function handle(PluginRegistry $registry): int
    {
        $registry->sync();

        $this->info('Plugin-Register abgeglichen.');

        return self::SUCCESS;
    }
}
