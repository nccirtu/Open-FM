<?php

namespace App\Support\Plugins;

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

final readonly class PluginDefinition
{
    /**
     * @param  list<string>  $requires
     * @param  list<string>  $publishTags
     */
    public function __construct(
        public string $slug,
        public string $package,
        public string $category,
        public string $icon,
        public array $requires = [],
        public array $publishTags = [],
    ) {}

    /**
     * @param  array{package: string, category?: string, icon?: string, requires?: list<string>, publish_tags?: list<string>}  $entry
     */
    public static function fromConfig(string $slug, array $entry): self
    {
        return new self(
            slug: $slug,
            package: $entry['package'],
            category: $entry['category'] ?? 'structure',
            icon: $entry['icon'] ?? 'heroicon-o-puzzle-piece',
            requires: $entry['requires'] ?? [],
            publishTags: $entry['publish_tags'] ?? [],
        );
    }

    public function name(): string
    {
        $key = "plugins.catalog.{$this->slug}.name";

        return Lang::has($key) ? __($key) : Str::headline($this->slug);
    }

    public function description(): string
    {
        $key = "plugins.catalog.{$this->slug}.description";

        return Lang::has($key) ? __($key) : '';
    }

    public function categoryLabel(): string
    {
        return __("plugins.categories.{$this->category}");
    }
}
