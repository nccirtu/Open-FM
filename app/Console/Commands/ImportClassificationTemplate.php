<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Services\Classification\ClassificationTemplateImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportClassificationTemplate extends Command
{
    protected $signature = 'classifications:import
        {file : Vorlage (.json) — Dateiname im Vorlagenverzeichnis oder absoluter Pfad}
        {--organization= : ID des Mandanten, der die Vorlage importiert}';

    protected $description = 'Importiert eine Klassifikations-Vorlage (z. B. DIN 276) für einen Mandanten — jeder Mandant importiert unabhängig, nichts wird global gesetzt.';

    public function handle(ClassificationTemplateImporter $importer): int
    {
        $path = File::exists($this->argument('file'))
            ? $this->argument('file')
            : $importer->templatePath($this->argument('file'));

        if (! File::exists($path)) {
            $this->error("Vorlage {$path} nicht gefunden.");

            return self::FAILURE;
        }

        $template = json_decode(File::get($path), true);

        if (! is_array($template) || ! is_array($template['nodes'] ?? null)) {
            $this->error('Die Datei enthält kein gültiges Vorlagen-JSON (erwartet: {"nodes": [...]}).');

            return self::FAILURE;
        }

        $organization = Organization::query()->find($this->option('organization'));

        if ($organization === null) {
            $this->error('Mandant nicht gefunden: --organization='.$this->option('organization'));

            return self::FAILURE;
        }

        $count = $importer->import($template['nodes'], $organization);
        $importer->importCustomFieldSections($template['custom_fields'] ?? []);

        $this->info("{$count} Knoten für „{$organization->name}“ importiert.");

        return self::SUCCESS;
    }
}
