<?php

namespace App\Support\Plugins\Installer;

use App\Enums\PluginSource;
use App\Enums\PluginStatus;
use App\Jobs\ProcessPluginOperation;
use App\Models\Plugin;
use App\Support\Plugins\Marketplace\MarketplaceClient;
use App\Support\Plugins\PluginManager;
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

class PluginInstaller
{
    public const OPERATIONS = ['install', 'update', 'remove', 'restore'];

    public function __construct(
        private PluginManager $plugins,
        private ComposerRunner $runner,
        private MarketplaceClient $marketplace,
        private PluginComposerFile $composerFile,
    ) {}

    /**
     * @return array{0: Plugin, 1: string}
     */
    public function stageUpload(string $zipPath, ?string $signature = null, bool $trustUnsigned = false): array
    {
        $archive = PluginArchive::open($zipPath, $this->plugins);

        $this->verifySignature($archive, $signature, $trustUnsigned);

        File::ensureDirectoryExists($this->artifactsPath());
        File::copy($zipPath, $this->artifactsPath().'/'.$archive->artifactFileName());

        $plugin = $this->record($archive->slug, $archive->package, PluginSource::Upload, $archive->manifest['name'] ?? null);

        return [$plugin, $archive->version];
    }

    public function stageMarketplace(string $slug): Plugin
    {
        $entry = $this->marketplace->catalog()->get($slug);
        $package = $entry['package'] ?? $this->plugins->catalog()->get($slug)?->package
            ?? throw new RuntimeException(__('plugins.installer.errors.unknown_plugin', ['plugin' => $slug]));

        $license = $this->marketplace->issueLicense($slug);

        $plugin = $this->record($slug, $package, PluginSource::Marketplace, $entry['name'] ?? null);
        $plugin->update([
            'license_key' => $license,
            'license_status' => 'active',
            'license_checked_at' => now(),
        ]);

        return $plugin;
    }

    public function dispatch(Plugin $plugin, string $operation, ?string $version = null, bool $sync = false): void
    {
        $plugin->update([
            'status' => match ($operation) {
                'install' => PluginStatus::Installing,
                'update' => PluginStatus::Updating,
                default => PluginStatus::Removing,
            },
            'last_error' => null,
        ]);

        $sync
            ? ProcessPluginOperation::dispatchSync($plugin->slug, $operation, $version)
            : ProcessPluginOperation::dispatch($plugin->slug, $operation, $version);
    }

    public function dispatchRestore(bool $sync = false): void
    {
        Plugin::query()->whereNotNull('installed_at')->update(['status' => PluginStatus::Installing, 'last_error' => null]);
        $this->plugins->flush();

        $sync
            ? ProcessPluginOperation::dispatchSync('*', 'restore')
            : ProcessPluginOperation::dispatch('*', 'restore');
    }

    /**
     * @return list<string>
     */
    public function missingPlugins(): array
    {
        return Plugin::query()
            ->whereNotNull('installed_at')
            ->pluck('slug')
            ->reject(fn (string $slug): bool => $this->plugins->installed($slug))
            ->values()
            ->all();
    }

    public function install(Plugin $plugin, ?string $version = null): void
    {
        $this->withPluginFileBackup(function () use ($plugin, $version): void {
            $this->composerFile->syncLock();
            $this->composerFile->write([$plugin->package => $this->composerFile->constraintFor($plugin, $version)]);
            $this->composer(['update', $plugin->package, '--with-dependencies']);
        });

        $plugin->update(['installed_at' => now()]);

        $this->afterComposerChange([$plugin->slug]);
    }

    public function update(Plugin $plugin): void
    {
        $this->withPluginFileBackup(function () use ($plugin): void {
            $this->composerFile->syncLock();
            $this->composerFile->write();
            $this->composer(['update', $plugin->package, '--with-dependencies']);
        });

        $this->afterComposerChange([$plugin->slug]);
    }

    public function remove(Plugin $plugin): void
    {
        $this->withPluginFileBackup(function () use ($plugin): void {
            $this->composerFile->syncLock();
            $this->composerFile->write(without: [$plugin->package]);
            $this->composer(['update', $plugin->package]);
        });

        $plugin->update(['installed_at' => null]);

        foreach ([['optimize:clear'], ['plugins:sync'], ['queue:restart']] as $command) {
            $this->expectSuccess($this->runner->artisan($command));
        }
    }

    public function restore(): void
    {
        $packages = array_keys($this->composerFile->registeredRequires());

        if ($packages === []) {
            return;
        }

        $this->withPluginFileBackup(function () use ($packages): void {
            $this->composerFile->syncLock();
            $this->composerFile->write();
            $this->composer(['update', ...$packages, '--with-dependencies']);
        });

        $this->afterComposerChange(Plugin::query()->whereIn('package', $packages)->pluck('slug')->all());
    }

    public function artifactsPath(): string
    {
        return (string) config('plugins.artifacts_path');
    }

    protected function verifySignature(PluginArchive $archive, ?string $signature, bool $trustUnsigned): void
    {
        $publicKey = config('plugins.public_key');

        if (filled($signature)) {
            if (blank($publicKey) || ! PluginSignature::verify($archive->path, $signature, $publicKey)) {
                throw new InvalidPluginArchive(__('plugins.installer.errors.invalid_signature'));
            }

            return;
        }

        if (config('plugins.require_signature') || ! $trustUnsigned) {
            throw new InvalidPluginArchive(__('plugins.installer.errors.unsigned'));
        }
    }

    protected function record(string $slug, string $package, PluginSource $source, ?string $name): Plugin
    {
        if (! preg_match('/^[a-z0-9]([_.-]?[a-z0-9]+)*\/[a-z0-9]([_.]|-{1,2})?([a-z0-9]+([_.]|-{1,2})?)*[a-z0-9]$/', $package) || ! preg_match('/^[a-z0-9][a-z0-9-]*$/', $slug)) {
            throw new InvalidPluginArchive(__('plugins.installer.errors.unknown_plugin', ['plugin' => $slug]));
        }

        $plugin = Plugin::query()->firstOrNew(['slug' => $slug]);

        if ($plugin->exists && $plugin->package !== $package) {
            throw new InvalidPluginArchive(__('plugins.installer.errors.slug_taken', ['slug' => $slug]));
        }

        $plugin->fill([
            'package' => $package,
            'name' => $name ?? $plugin->name ?? ($this->plugins->catalog()->get($slug)?->name() ?? Str::headline($slug)),
            'source' => $source,
        ]);

        if (! $plugin->exists) {
            $plugin->fill(['status' => PluginStatus::Installed, 'is_enabled' => true]);
        }

        $plugin->save();

        return $plugin;
    }

    /**
     * @param  list<string>  $slugs
     */
    protected function afterComposerChange(array $slugs): void
    {
        $commands = [['migrate', '--force'], ['filament:assets']];

        foreach ($slugs as $slug) {
            foreach ($this->plugins->catalog()->get($slug)?->publishTags ?? [] as $tag) {
                $commands[] = ['vendor:publish', '--tag='.$tag, '--force'];
            }
        }

        $commands[] = ['plugins:sync'];
        $commands[] = ['optimize:clear'];
        $commands[] = ['queue:restart'];

        foreach ($commands as $command) {
            $this->expectSuccess($this->runner->artisan($command));
        }
    }

    /**
     * @param  list<string>  $arguments
     */
    protected function composer(array $arguments): void
    {
        $this->expectSuccess($this->runner->composer($arguments, $this->composerFile->environment()));
    }

    protected function withPluginFileBackup(callable $callback): void
    {
        $backup = [];

        foreach ([PluginComposerFile::FILE, PluginComposerFile::LOCK] as $file) {
            $path = $this->composerFile->path($file);
            $backup[$path] = File::exists($path) ? File::get($path) : null;
        }

        try {
            $callback();
        } catch (RuntimeException $exception) {
            foreach ($backup as $path => $contents) {
                $contents === null ? File::delete($path) : File::put($path, $contents);
            }

            throw $exception;
        }
    }

    protected function expectSuccess(ProcessResult $result): ProcessResult
    {
        if ($result->failed()) {
            throw new RuntimeException(trim(Str::limit($result->errorOutput() ?: $result->output(), 4000)));
        }

        return $result;
    }
}
