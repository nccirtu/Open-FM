<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Services\Waste\WasteTemplateImporter;
use Illuminate\Console\Command;

class ImportWasteTemplate extends Command
{
    protected $signature = 'waste:import-template
        {template : Schlüssel der Länder-Vorlage, z. B. de}
        {--organization= : Slug oder ID des Mandanten}';

    protected $description = 'Importiert eine Länder-Vorlage des Abfall-Bereichs (Fraktionen, Land) für einen Mandanten';

    public function handle(WasteTemplateImporter $importer): int
    {
        $key = (string) $this->argument('template');

        if (! array_key_exists($key, WasteTemplateImporter::TEMPLATES)) {
            $this->error("Unbekannte Vorlage „{$key}“. Verfügbar: ".implode(', ', array_keys(WasteTemplateImporter::TEMPLATES)));

            return self::FAILURE;
        }

        if (! array_key_exists($key, WasteTemplateImporter::templateOptions())) {
            $this->error('Vorlagendatei fehlt: '.$importer->templatePath(WasteTemplateImporter::TEMPLATES[$key]));

            return self::FAILURE;
        }

        $value = (string) $this->option('organization');
        $organization = Organization::query()->where('slug', $value)->orWhere('id', $value)->first();

        if ($organization === null) {
            $this->error("Mandant nicht gefunden: --organization={$value}");

            return self::FAILURE;
        }

        $count = $importer->importTemplate($organization, $key);

        $this->info("{$count} Fraktionen für „{$organization->name}“ importiert.");

        return self::SUCCESS;
    }
}
