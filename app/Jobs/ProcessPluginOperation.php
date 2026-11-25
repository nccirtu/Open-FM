<?php

namespace App\Jobs;

use App\Enums\PluginStatus;
use App\Models\Plugin;
use App\Support\Plugins\Installer\PluginInstaller;
use App\Support\Plugins\PluginManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;

class ProcessPluginOperation implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1800;

    public int $tries = 1;

    public function __construct(
        public string $slug,
        public string $operation,
        public ?string $version = null,
    ) {}

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping('plugin-operations'))->releaseAfter(30)->expireAfter($this->timeout)];
    }

    public function handle(PluginInstaller $installer): void
    {
        if ($this->operation === 'restore') {
            $this->restore($installer);

            return;
        }

        $plugin = Plugin::query()->where('slug', $this->slug)->firstOrFail();

        try {
            match ($this->operation) {
                'install' => $installer->install($plugin, $this->version),
                'update' => $installer->update($plugin),
                'remove' => $installer->remove($plugin),
            };

            $plugin->refresh()->update(['status' => PluginStatus::Installed, 'last_error' => null]);
        } catch (Throwable $exception) {
            $plugin->refresh()->update(['status' => PluginStatus::Failed, 'last_error' => $exception->getMessage()]);
        }
    }

    protected function restore(PluginInstaller $installer): void
    {
        $plugins = Plugin::query()->whereNotNull('installed_at');

        try {
            $installer->restore();

            $plugins->update(['status' => PluginStatus::Installed, 'last_error' => null]);
        } catch (Throwable $exception) {
            $plugins->update(['status' => PluginStatus::Failed, 'last_error' => $exception->getMessage()]);
        }

        app(PluginManager::class)->flush();
    }
}
