<?php

namespace App\Support\Plugins\Installer;

use App\Enums\PluginSource;
use App\Models\Plugin;
use App\Support\Plugins\Marketplace\MarketplaceClient;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PluginComposerFile
{
    public const FILE = 'composer.plugins.json';

    public const LOCK = 'composer.plugins.lock';

    public function __construct(private MarketplaceClient $marketplace) {}

    /**
     * @param  array<string, string>  $requires
     * @param  list<string>  $without
     */
    public function write(array $requires = [], array $without = []): void
    {
        $composer = json_decode(File::get($this->path('composer.json')), true);

        foreach ([...$this->registeredRequires(), ...$requires] as $package => $constraint) {
            $composer['require'][$package] = $constraint;
        }

        foreach ($without as $package) {
            unset($composer['require'][$package]);
        }

        $composer['repositories'] = [...$this->repositories(), ...array_values($composer['repositories'] ?? [])];

        File::put($this->path(self::FILE), json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");
    }

    public function syncLock(): bool
    {
        $projectLock = $this->path('composer.lock');

        if (! File::exists($projectLock)) {
            return false;
        }

        $hash = sha1_file($projectLock);
        $marker = $this->markerPath();

        if (File::exists($this->path(self::LOCK)) && File::exists($marker) && trim(File::get($marker)) === $hash) {
            return false;
        }

        File::copy($projectLock, $this->path(self::LOCK));
        File::ensureDirectoryExists(dirname($marker));
        File::put($marker, $hash);

        return true;
    }

    /**
     * @return array<string, string>
     */
    public function environment(): array
    {
        $environment = ['COMPOSER' => self::FILE];
        $token = $this->marketplace->repositoryToken();

        if ($this->marketplace->configured() && filled($token)) {
            $environment['COMPOSER_AUTH'] = json_encode(['http-basic' => [
                $this->marketplace->host() => ['username' => MarketplaceClient::installationId(), 'password' => $token],
            ]]);
        }

        return $environment;
    }

    /**
     * @return array<string, string>
     */
    public function registeredRequires(): array
    {
        return Plugin::query()
            ->whereNotNull('installed_at')
            ->get()
            ->mapWithKeys(fn (Plugin $plugin): array => [$plugin->package => $this->constraintFor($plugin)])
            ->all();
    }

    public function constraintFor(Plugin $plugin, ?string $version = null): string
    {
        if (filled(config('plugins.dev_path'))) {
            return '*@dev';
        }

        return match (true) {
            filled($version) => $version,
            $plugin->source === PluginSource::Upload && filled($plugin->version) => $plugin->version,
            default => '*',
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function repositories(): array
    {
        $repositories = [];

        if (filled($devPath = config('plugins.dev_path'))) {
            $repositories[] = ['type' => 'path', 'url' => rtrim($devPath, '/').'/*', 'options' => ['symlink' => true]];
        }

        $repositories[] = ['type' => 'artifact', 'url' => Str::after((string) config('plugins.artifacts_path'), $this->path(''))];

        if ($this->marketplace->configured()) {
            $repositories[] = ['type' => 'composer', 'url' => $this->marketplace->repositoryUrl()];
        }

        return $repositories;
    }

    public function path(string $relative): string
    {
        return rtrim((string) config('plugins.composer.working_dir', base_path()), '/').'/'.$relative;
    }

    protected function markerPath(): string
    {
        return $this->path('storage/app/private/plugins/composer-lock-base.sha1');
    }
}
