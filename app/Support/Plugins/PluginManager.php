<?php

namespace App\Support\Plugins;

use App\Models\Organization;
use App\Models\Plugin;
use App\Models\User;
use Composer\InstalledVersions;
use Filament\Facades\Filament;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Throwable;

class PluginManager
{
    /** @var array<string, true> */
    protected array $simulatedMissing = [];

    /** @var Collection<string, Plugin>|null */
    protected ?Collection $records = null;

    /**
     * @return Collection<string, PluginDefinition>
     */
    public function catalog(): Collection
    {
        return collect(config('plugins.catalog', []))
            ->map(fn (array $entry, string $slug): PluginDefinition => PluginDefinition::fromConfig($slug, $entry));
    }

    public function definition(string $slug): PluginDefinition
    {
        return $this->catalog()->get($slug) ?? throw new InvalidArgumentException("Unbekanntes Plugin '{$slug}'.");
    }

    public function installed(string $slug): bool
    {
        if (isset($this->simulatedMissing[$slug])) {
            return false;
        }

        $package = $this->catalog()->get($slug)?->package ?? $this->record($slug)?->package;

        return $package !== null && InstalledVersions::isInstalled($package);
    }

    public function enabled(string $slug): bool
    {
        if (! $this->installed($slug) || ($this->record($slug)?->is_enabled ?? true) === false) {
            return false;
        }

        foreach ($this->catalog()->get($slug)?->requires ?? [] as $dependency) {
            if (! $this->enabled($dependency)) {
                return false;
            }
        }

        return true;
    }

    public function availableFor(string $slug, ?Organization $organization): bool
    {
        return $this->enabled($slug)
            && ($this->record($slug)?->isAvailableFor($organization) ?? true);
    }

    public function active(string $slug): bool
    {
        $tenant = Filament::getTenant();

        return $this->availableFor($slug, $tenant instanceof Organization ? $tenant : null);
    }

    public function unavailableReason(string $slug): string
    {
        $name = $this->definition($slug)->name();

        return match (true) {
            ! $this->installed($slug) => __('plugins.required.not_installed', ['plugin' => $name]),
            ! $this->enabled($slug) => __('plugins.required.disabled', ['plugin' => $name]),
            default => __('plugins.required.not_available_for_tenant', ['plugin' => $name]),
        };
    }

    public function canManage(?User $user = null): bool
    {
        $user ??= auth()->user();

        return $user instanceof User && $user->isSuperAdmin();
    }

    public function record(string $slug): ?Plugin
    {
        return $this->records()->get($slug);
    }

    /**
     * @return Collection<string, Plugin>
     */
    public function records(): Collection
    {
        if ($this->records !== null) {
            return $this->records;
        }

        try {
            return $this->records = Plugin::query()->with('organizations')->get()->keyBy('slug');
        } catch (Throwable) {
            return collect();
        }
    }

    public function flush(): void
    {
        $this->records = null;
    }

    public function simulateMissing(string ...$slugs): void
    {
        foreach ($slugs as $slug) {
            $this->simulatedMissing[$slug] = true;
        }
    }
}
