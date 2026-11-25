<?php

namespace App\Services;

use App\Models\AssetType;
use App\Models\MaintenancePlanTemplate;
use App\Models\Organization;

class MaintenanceCatalogImporter
{
    public const COUNTRIES = ['de', 'fr', 'pl'];

    public function __construct(private MaintenancePlanGenerator $generator) {}

    /**
     * @return array<string, string>
     */
    public static function countryOptions(): array
    {
        return collect(self::COUNTRIES)
            ->mapWithKeys(fn (string $country): array => [$country => __("maintenance.catalogs.countries.{$country}")])
            ->all();
    }

    public function import(Organization $organization, string $country): int
    {
        if (! in_array($country, self::COUNTRIES, true)) {
            return 0;
        }

        $entries = require database_path("seeders/data/maintenance_catalogs/{$country}.php");

        $imported = 0;

        foreach ($entries as $entry) {
            $assetType = AssetType::query()->firstOrCreate(['name' => $entry['asset_type']]);

            $template = MaintenancePlanTemplate::query()
                ->withoutGlobalScopes()
                ->where('organization_id', $organization->getKey())
                ->where('asset_type_id', $assetType->getKey())
                ->where('name', $entry['name'])
                ->first();

            if ($template === null) {
                $template = new MaintenancePlanTemplate([
                    'asset_type_id' => $assetType->getKey(),
                    'country' => strtoupper($country),
                    'name' => $entry['name'],
                    'type' => $entry['type'],
                    'legal_basis' => $entry['legal_basis'] ?? null,
                    'interval_value' => $entry['interval'][0],
                    'interval_unit' => $entry['interval'][1],
                    'lead_time_days' => $entry['lead_time_days'] ?? 14,
                    'is_active' => true,
                    'notes' => $entry['notes'] ?? null,
                    'tasks' => $entry['tasks'] ?? null,
                ]);

                $template->organization_id = $organization->getKey();

                $template->saveQuietly();

                $imported++;
            }

            $this->generator->forTemplate($template);
        }

        return $imported;
    }
}
