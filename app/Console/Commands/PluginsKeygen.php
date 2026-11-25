<?php

namespace App\Console\Commands;

use App\Support\Plugins\Installer\PluginSignature;
use Illuminate\Console\Command;

class PluginsKeygen extends Command
{
    protected $signature = 'plugins:keygen';

    protected $description = 'Erzeugt ein Ed25519-Schlüsselpaar zum Signieren von Plugin-ZIPs (für den Herausgeber)';

    public function handle(): int
    {
        $keys = PluginSignature::generateKeyPair();

        $this->line('Öffentlicher Schlüssel (in Installationen als PLUGINS_PUBLIC_KEY):');
        $this->info($keys['public']);
        $this->newLine();
        $this->line('Geheimer Schlüssel (nur beim Herausgeber aufbewahren, nie einchecken):');
        $this->warn($keys['secret']);

        return self::SUCCESS;
    }
}
