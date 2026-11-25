<?php

namespace App\Console\Commands;

use App\Enums\PluginStatus;
use App\Support\Plugins\Installer\InvalidPluginArchive;
use App\Support\Plugins\Installer\PluginInstaller;
use App\Support\Plugins\Marketplace\MarketplaceException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PluginsInstall extends Command
{
    protected $signature = 'plugins:install
        {plugin? : Plugin aus dem Shop (Slug, z. B. qr-code)}
        {--zip= : Pfad zu einer Plugin-ZIP statt Shop}
        {--signature= : Base64-Signatur der ZIP oder Pfad zur .sig-Datei}
        {--trust-unsigned : Unsignierte ZIP installieren}';

    protected $description = 'Installiert ein Plugin aus dem Shop oder aus einer ZIP-Datei (ohne Queue)';

    public function handle(PluginInstaller $installer): int
    {
        try {
            if (filled($zip = $this->option('zip'))) {
                if (! File::exists($zip)) {
                    $this->error("Datei '{$zip}' nicht gefunden.");

                    return self::FAILURE;
                }

                $signature = $this->option('signature');
                $signature = filled($signature) && File::exists($signature) ? File::get($signature) : $signature;

                [$plugin, $version] = $installer->stageUpload($zip, $signature, (bool) $this->option('trust-unsigned'));
            } elseif (filled($slug = $this->argument('plugin'))) {
                $plugin = $installer->stageMarketplace($slug);
                $version = null;
            } else {
                $this->error('Plugin (Slug) oder --zip angeben.');

                return self::FAILURE;
            }
        } catch (InvalidPluginArchive|MarketplaceException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Installiere '{$plugin->package}' …");
        $installer->dispatch($plugin, 'install', $version, sync: true);

        return $this->report($plugin->refresh()->status, $plugin->last_error, "'{$plugin->package}' ist installiert.");
    }

    protected function report(PluginStatus $status, ?string $error, string $success): int
    {
        if ($status === PluginStatus::Failed) {
            $this->error((string) $error);

            return self::FAILURE;
        }

        $this->info($success);

        return self::SUCCESS;
    }
}
