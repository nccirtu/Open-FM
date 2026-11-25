<?php

namespace App\Support\Plugins\Installer;

use App\Support\Plugins\PluginManager;
use Illuminate\Support\Str;
use ZipArchive;

final readonly class PluginArchive
{
    /**
     * @param  array<string, mixed>  $manifest
     */
    private function __construct(
        public string $path,
        public string $package,
        public string $version,
        public string $slug,
        public array $manifest,
    ) {}

    public static function open(string $path, PluginManager $plugins): self
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new InvalidPluginArchive(__('plugins.installer.errors.not_a_zip'));
        }

        try {
            $composer = self::readComposerJson($zip);
        } finally {
            $zip->close();
        }

        $package = $composer['name'] ?? null;
        $version = $composer['version'] ?? null;

        if (! is_string($package) || preg_match('#^[a-z0-9]([_.-]?[a-z0-9]+)*/[a-z0-9](([_.]|-{1,2})?[a-z0-9]+)*$#', $package) !== 1) {
            throw new InvalidPluginArchive(__('plugins.installer.errors.missing_name'));
        }

        if (! is_string($version) || $version === '') {
            throw new InvalidPluginArchive(__('plugins.installer.errors.missing_version'));
        }

        $manifest = (array) ($composer['extra']['open-fm'] ?? []);
        $catalogSlug = $plugins->catalog()->first(fn ($definition): bool => $definition->package === $package)?->slug;
        $slug = $catalogSlug ?? ($manifest['slug'] ?? null);

        if (! is_string($slug) || $slug === '') {
            throw new InvalidPluginArchive(__('plugins.installer.errors.not_a_plugin'));
        }

        if ($catalogSlug === null && $plugins->catalog()->has($slug)) {
            throw new InvalidPluginArchive(__('plugins.installer.errors.slug_taken', ['slug' => $slug]));
        }

        return new self($path, $package, $version, Str::slug($slug), $manifest);
    }

    public function artifactFileName(): string
    {
        return str_replace('/', '-', $this->package).'-'.preg_replace('/[^A-Za-z0-9._-]/', '-', $this->version).'.zip';
    }

    /**
     * @return array<string, mixed>
     */
    private static function readComposerJson(ZipArchive $zip): array
    {
        $best = null;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);

            if (basename($name) !== 'composer.json' || substr_count(trim($name, '/'), '/') > 1) {
                continue;
            }

            if ($best === null || substr_count($name, '/') < substr_count($best, '/')) {
                $best = $name;
            }
        }

        $json = $best === null ? false : $zip->getFromName($best);
        $data = is_string($json) ? json_decode($json, true) : null;

        if (! is_array($data)) {
            throw new InvalidPluginArchive(__('plugins.installer.errors.missing_composer_json'));
        }

        return $data;
    }
}
