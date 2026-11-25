<?php

namespace App\Console\Commands;

use App\Models\MaintenanceOrder;
use App\Models\MaintenancePlan;
use Illuminate\Console\Command;

class GenerateMaintenanceOrders extends Command
{
    protected $signature = 'maintenance:generate-orders';

    protected $description = 'Erzeugt fällige Wartungs-/Prüfaufträge aus aktiven Plänen (unter Berücksichtigung der Vorlaufzeit)';

    public function handle(): int
    {
        $created = 0;

        MaintenancePlan::query()
            ->where('is_active', true)
            ->get()
            ->filter(fn (MaintenancePlan $plan): bool => $plan->next_due_at->lessThanOrEqualTo(now()->addDays($plan->lead_time_days)))
            ->each(function (MaintenancePlan $plan) use (&$created): void {
                $order = MaintenanceOrder::firstOrCreateForPlanDueDate($plan);

                if ($order->wasRecentlyCreated) {
                    $created++;
                }
            });

        $this->info("{$created} Wartungsaufträge erzeugt.");

        return self::SUCCESS;
    }
}
