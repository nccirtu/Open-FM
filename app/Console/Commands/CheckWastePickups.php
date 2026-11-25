<?php

namespace App\Console\Commands;

use App\Services\Waste\PickupMonitor;
use Illuminate\Console\Command;

class CheckWastePickups extends Command
{
    protected $signature = 'waste:check-pickups';

    protected $description = 'Abfuhrtermine prüfen: Erinnerung „bereitstellen“, nach der Stichzeit laut Plan als erledigt buchen, an offene Abrufe erinnern';

    public function handle(PickupMonitor $monitor): int
    {
        $result = $monitor->run();

        $this->info(sprintf(
            'Bereitstellung: %d · laut Plan erledigt: %d · Abrufe: %d',
            $result['staging'],
            $result['completed'],
            $result['requests'],
        ));

        return self::SUCCESS;
    }
}
