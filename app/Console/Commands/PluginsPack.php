<?php

namespace App\Console\Commands;

use App\Support\Plugins\Installer\PluginSignature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use ZipArchive;

class PluginsPack extends Command
{
    protected $signature = 'plugins:pack
        {path : Verzeichnis des Plugin-Pakets (mit composer.json)}
        {--package-version= : Version (Standard: Version aus composer.json)}
        {--output= : Zielverzeichnis (Standard: storage/app/private/plugins/dist)}
        {--key= : Geheimer Signaturschlüssel (Base64), sonst plugins.secret_key (PLUGINS_SECRET_KEY)}';

    protected $description = 'Packt ein Plugin-Paket als installierbare, optional signierte ZIP';

    /**
     * @var list<string>
     */
    private const EXCLUDE = ['.git', 'node_modules', 'vendor', 'tests', '.github', '.idea', '.DS_Store', 'composer.lock', 'phpunit.xml', '.phpunit.result.cache'];

    public function handle(): int
    {
        $path = rtrim((string) realpath($this->argument('path')), '/');

        if ($path === '' || ! File::exists($path.'/composer.json')) {
            $this->error("Kein Plugin-Paket unter '{$this->argument('path')}' (composer.json fehlt).");

            return self::FAILURE;
        }

        $composer = json_decode(File::get($path.'/composer.json'), true);
        $version = $this->option('package-version') ?: ($composer['version'] ?? null);

        if (! is_array($composer) || blank($composer['name'] ?? null) || blank($version)) {
            $this->error('composer.json braucht einen Namen; die Version über --package-version oder in der composer.json angeben.');

            return self::FAILURE;
        }

        $composer['version'] = $version;
        $output = $this->option('output') ?: storage_path('app/private/plugins/dist');
        File::ensureDirectoryExists($output);

        $zipPath = $output.'/'.str_replace('/', '-', $composer['name']).'-'.$version.'.zip';
        File::delete($zipPath);

        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE);
        $zip->addFromString('composer.json', json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS));

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            $relative = substr($file->getPathname(), strlen($path) + 1);

            if ($relative === 'composer.json' || $this->excluded($relative)) {
                continue;
            }

            $zip->addFile($file->getPathname(), $relative);
        }

        $zip->close();
        $this->info("ZIP erstellt: {$zipPath}");

        $secretKey = $this->option('key') ?: config('plugins.secret_key');

        if (filled($secretKey)) {
            File::put($zipPath.'.sig', PluginSignature::sign($zipPath, $secretKey));
            $this->info("Signatur erstellt: {$zipPath}.sig");
        }

        return self::SUCCESS;
    }

    private function excluded(string $relative): bool
    {
        foreach (explode('/', $relative) as $segment) {
            if (in_array($segment, self::EXCLUDE, true)) {
                return true;
            }
        }

        return false;
    }
}
