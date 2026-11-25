<?php

namespace App\Services\Waste;

use App\Models\Organization;
use App\Models\WasteFraction;
use App\Models\WasteFractionGroup;
use App\Models\WasteSettings;
use App\Models\WasteTreatment;
use Illuminate\Support\Facades\File;

/**
 * @phpstan-type FractionSpec array{code?: string, name: string, hazardous?: bool, group?: string, density_kg_m3?: float|int, treatment?: string, color?: string}
 */
class WasteTemplateImporter
{
    /**
     * @var array<string, string>
     */
    public const TEMPLATES = [
        'de' => 'de.json',
        'at' => 'at.json',
        'ch' => 'ch.json',
        'lu' => 'lu.json',
        'fr' => 'fr.json',
        'be' => 'be.json',
        'be_wal' => 'be_wal.json',
        'be_bru' => 'be_bru.json',
        'nl' => 'nl.json',
        'it' => 'it.json',
        'pl' => 'pl.json',
        'hr' => 'hr.json',
        'ro' => 'ro.json',
        'gb' => 'gb.json',
        'ie' => 'ie.json',
    ];

    public function __construct(
        private WasteLookupDefaults $defaults,
        private ?string $templatesPath = null,
    ) {}

    public function templatesPath(): string
    {
        return $this->templatesPath ?? database_path('templates/waste');
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
            ->mapWithKeys(fn (string $key): array => [$key => __("waste.templates.{$key}")])
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

        /** @var array{fractions?: array<int, FractionSpec>, rules?: array<string, mixed>, sources?: array<string, array<string, string>>, evidence_types?: array<int, string>} $template */
        $template = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);

        $this->defaults->seed($organization);

        $created = 0;

        foreach (array_values($template['fractions'] ?? []) as $sort => $spec) {
            if ($this->importFraction($organization, $templateKey, $spec, $sort)) {
                $created++;
            }
        }

        $settings = WasteSettings::forOrganization($organization->getKey());
        $settings->country = $templateKey;
        $settings->rules = $template['rules'] ?? $settings->rules;
        $settings->rule_sources = $template['sources'] ?? $settings->rule_sources;
        $settings->evidence_types = $template['evidence_types'] ?? $settings->evidence_types;
        $settings->save();

        return $created;
    }

    private function importFraction(Organization $organization, string $templateKey, array $spec, int $sort): bool
    {
        $query = WasteFraction::query()
            ->withoutGlobalScopes()
            ->where('organization_id', $organization->getKey());

        $exists = filled($spec['code'] ?? null)
            ? (clone $query)->where('code', $spec['code'])->exists()
            : (clone $query)->where('name', $spec['name'])->exists();

        if ($exists) {
            return false;
        }

        $fraction = new WasteFraction([
            'name' => $spec['name'],
            'code' => $spec['code'] ?? null,
            'is_hazardous' => (bool) ($spec['hazardous'] ?? str_ends_with((string) ($spec['code'] ?? ''), '*')),
            'waste_fraction_group_id' => filled($spec['group'] ?? null)
                ? WasteFractionGroup::query()->withoutGlobalScopes()->where('organization_id', $organization->getKey())->where('key', $spec['group'])->value('id')
                : null,
            'color' => $spec['color'] ?? null,
            'density_kg_m3' => $spec['density_kg_m3'] ?? null,
            'waste_treatment_id' => filled($spec['treatment'] ?? null)
                ? WasteTreatment::query()->withoutGlobalScopes()->where('organization_id', $organization->getKey())->where('code', $spec['treatment'])->value('id')
                : null,
            'template_key' => $templateKey,
            'is_active' => true,
            'sort' => $sort,
        ]);
        $fraction->organization_id = $organization->getKey();
        $fraction->save();

        return true;
    }
}
