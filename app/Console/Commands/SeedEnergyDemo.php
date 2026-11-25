<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Services\Energy\EnergyDemoData;
use Illuminate\Console\Command;

class SeedEnergyDemo extends Command
{
    protected $signature = 'energy:demo
        {--organization=demo : Slug oder ID des Mandanten}
        {--remove : Nur die Demo-Daten entfernen}';

    protected $description = 'Legt Demo-Objekte mit Anlagen, Zählern, Ablesungen und Zielen für den Energie-Bereich an (ersetzt vorhandene Demo-Daten).';

    public function handle(EnergyDemoData $demo): int
    {
        $key = (string) $this->option('organization');
        $organization = Organization::query()->where('slug', $key)->orWhere('id', $key)->first();

        if ($organization === null) {
            $this->components->error("Mandant nicht gefunden: --organization={$key}");

            return self::FAILURE;
        }

        if ($this->option('remove')) {
            $count = $demo->remove($organization);
            $this->components->info("{$count} Demo-Objekte von „{$organization->name}“ entfernt.");

            return self::SUCCESS;
        }

        $result = $demo->seed($organization);

        $this->components->info("Demo-Daten für „{$organization->name}“ angelegt.");
        $this->components->twoColumnDetail('Objekte', implode(', ', $result['properties']));
        $this->components->twoColumnDetail('Zähler', (string) $result['meters']);
        $this->components->twoColumnDetail('Ablesungen', number_format($result['readings'], 0, ',', '.'));
        $this->components->twoColumnDetail('Ziele', (string) $result['goals']);

        return self::SUCCESS;
    }
}
