<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Services\Waste\WasteDemoData;
use Illuminate\Console\Command;

class SeedWasteDemo extends Command
{
    protected $signature = 'waste:demo
        {--organization=demo : Slug oder ID des Mandanten}
        {--remove : Nur die Demo-Daten entfernen}';

    protected $description = 'Legt Demo-Daten für den Abfall-Bereich an: Objekte, Behälter, Pläne, Leerungen, Rechnungen, Register, Nachweise, Ziele, Bauabfälle, Sensoren (ersetzt vorhandene Demo-Daten).';

    public function handle(WasteDemoData $demo): int
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
        $this->components->twoColumnDetail('Behälter', (string) $result['containers']);
        $this->components->twoColumnDetail('Leerungen', number_format($result['collections'], 0, ',', '.'));
        $this->components->twoColumnDetail('Künftige Termine', (string) $result['pickups']);
        $this->components->twoColumnDetail('Umbauprojekt (PM)', WasteDemoData::PROJECT_CODE);

        return self::SUCCESS;
    }
}
