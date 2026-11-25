<?php

namespace App\Console\Commands;

use App\Services\Waste\PickupGenerator;
use Illuminate\Console\Command;

class GenerateWastePickups extends Command
{
    protected $signature = 'waste:generate-pickups';

    protected $description = 'Erzeugt die Abfuhrtermine aller aktiven Abfuhrpläne für den Vorlauf aus den Einstellungen (Standard 8 Wochen)';

    public function handle(PickupGenerator $generator): int
    {
        $created = $generator->generateAll();

        $this->info("{$created} Abfuhrtermine angelegt.");

        return self::SUCCESS;
    }
}
