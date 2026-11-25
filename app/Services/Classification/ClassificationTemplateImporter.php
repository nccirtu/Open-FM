<?php

namespace App\Services\Classification;

use App\Models\AssetType;
use App\Models\AssetTypeClassification;
use App\Models\ClassificationNode;
use App\Models\Organization;
use App\Support\Plugins\CustomFieldsBridge;
use App\Support\Plugins\Plugins;
use Cafm\CustomFields\Models\CustomFieldSection;
use Cafm\CustomFields\Support\CustomFieldTemplate;
use Illuminate\Support\Facades\File;

/**
 * @phpstan-type NodeSpec array{code: string, name: string, asset_types?: array<int, string>, children?: array<int, NodeSpec>}
 */
class ClassificationTemplateImporter
{
    /**
     * @var array<string, string>
     */
    public const TEMPLATES = [
        'din276' => 'din-276.json',
    ];

    public function __construct(private ?string $templatesPath = null) {}

    public function templatesPath(): string
    {
        return $this->templatesPath ?? database_path('templates/classifications');
    }

    public function templatePath(string $file): string
    {
        return $this->templatesPath().DIRECTORY_SEPARATOR.$file;
    }

    /**
     * @return array<string, string>
     */
    public static function templateOptions(): array
    {
        $importer = app(self::class);

        return collect(self::TEMPLATES)
            ->filter(fn (string $file): bool => File::exists($importer->templatePath($file)))
            ->keys()
            ->mapWithKeys(fn (string $key): array => [$key => __("classifications.templates.{$key}")])
            ->all();
    }

    public function importTemplate(Organization $organization, string $templateKey): int
    {
        if (! array_key_exists($templateKey, self::TEMPLATES)) {
            return 0;
        }

        $path = $this->templatePath(self::TEMPLATES[$templateKey]);

        if (! File::exists($path)) {
            return 0;
        }

        $template = json_decode(File::get($path), true);

        if (! is_array($template)) {
            return 0;
        }

        $count = $this->import($template['nodes'] ?? [], $organization);

        $this->importCustomFieldSections($template['custom_fields'] ?? []);

        return $count;
    }

    /**
     * @param  array<int, NodeSpec>  $nodes
     */
    public function import(array $nodes, Organization $organization): int
    {
        return $this->importLevel($nodes, $organization, null);
    }

    /**
     * @param  array<int, NodeSpec>  $nodes
     */
    private function importLevel(array $nodes, Organization $organization, ?string $parentId): int
    {
        $imported = 0;

        foreach ($nodes as $sort => $node) {
            if (blank($node['code'] ?? null) || blank($node['name'] ?? null)) {
                continue;
            }

            $existing = ClassificationNode::query()
                ->where('organization_id', $organization->getKey())
                ->where('code', $node['code'])
                ->first();

            if ($existing === null) {
                $existing = ClassificationNode::query()->create([
                    'organization_id' => $organization->getKey(),
                    'parent_id' => $parentId,
                    'code' => $node['code'],
                    'name' => $node['name'],
                    'sort' => $sort,
                ]);
                $imported++;
            }

            $this->assignAssetTypes($node['asset_types'] ?? [], $existing, $organization);

            $imported += $this->importLevel($node['children'] ?? [], $organization, $existing->getKey());
        }

        return $imported;
    }

    /**
     * @param  array<int, string>  $names
     */
    private function assignAssetTypes(array $names, ClassificationNode $node, Organization $organization): void
    {
        foreach ($names as $name) {
            $type = AssetType::query()->firstOrCreate(['name' => $name]);

            AssetTypeClassification::query()->firstOrCreate(
                ['organization_id' => $organization->getKey(), 'asset_type_id' => $type->getKey()],
                ['classification_node_id' => $node->getKey()],
            );
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $sections
     */
    public function importCustomFieldSections(array $sections): void
    {
        if (! Plugins::installed(CustomFieldsBridge::PLUGIN)) {
            return;
        }

        foreach ($sections as $section) {
            if (blank($section['entity'] ?? null) || blank($section['key'] ?? null)) {
                continue;
            }

            $exists = CustomFieldSection::query()
                ->where('entity', $section['entity'])
                ->where('key', $section['key'])
                ->exists();

            if ($exists) {
                continue;
            }

            CustomFieldTemplate::import(['custom_fields_template' => CustomFieldTemplate::VERSION, ...$section]);
        }
    }
}
