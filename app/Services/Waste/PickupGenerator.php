<?php

namespace App\Services\Waste;

use App\Enums\WastePickupKind;
use App\Enums\WastePickupStatus;
use App\Models\WasteContainer;
use App\Models\WastePickup;
use App\Models\WasteSchedule;
use App\Models\WasteSettings;
use Carbon\CarbonImmutable;

final class PickupGenerator
{
    public function __construct(private ScheduleOccurrences $occurrences) {}

    public function generate(WasteSchedule $schedule, ?CarbonImmutable $today = null): int
    {
        $today = ($today ?? CarbonImmutable::today())->startOfDay();
        $settings = WasteSettings::forOrganization($schedule->organization_id);
        $containers = $schedule->is_active
            ? $schedule->containers()->withoutGlobalScopes()->where('waste_containers.is_active', true)->get()
            : collect();

        $dates = $containers->isEmpty()
            ? []
            : $this->occurrences->between($schedule, $today, $today->addWeeks($settings->horizon_weeks));

        $wanted = [];
        $created = 0;

        foreach ($containers as $container) {
            /** @var WasteContainer $container */
            foreach ($dates as $date) {
                $wanted[$container->getKey().'|'.$date->toDateString()] = true;

                $exists = WastePickup::query()
                    ->withoutGlobalScopes()
                    ->where('waste_schedule_id', $schedule->getKey())
                    ->where('waste_container_id', $container->getKey())
                    ->whereDate('due_on', $date->toDateString())
                    ->exists();

                if ($exists) {
                    continue;
                }

                $pickup = new WastePickup([
                    'waste_schedule_id' => $schedule->getKey(),
                    'waste_container_id' => $container->getKey(),
                    'property_id' => $container->property_id,
                    'due_on' => $date->toDateString(),
                    'kind' => WastePickupKind::Regular,
                    'status' => WastePickupStatus::Planned,
                ]);
                $pickup->organization_id = $schedule->organization_id;
                $pickup->save();

                $created++;
            }
        }

        $this->prune($schedule, $today, $wanted);

        return $created;
    }

    public function generateAll(?string $organizationId = null, ?CarbonImmutable $today = null): int
    {
        $created = 0;

        WasteSchedule::query()
            ->withoutGlobalScopes()
            ->when($organizationId !== null, fn ($query) => $query->where('organization_id', $organizationId))
            ->each(function (WasteSchedule $schedule) use (&$created, $today): void {
                $created += $this->generate($schedule, $today);
            });

        return $created;
    }

    /**
     * @param  array<string, true>  $wanted
     */
    private function prune(WasteSchedule $schedule, CarbonImmutable $today, array $wanted): void
    {
        WastePickup::query()
            ->withoutGlobalScopes()
            ->where('waste_schedule_id', $schedule->getKey())
            ->where('status', WastePickupStatus::Planned->value)
            ->where('kind', WastePickupKind::Regular->value)
            ->whereDate('due_on', '>=', $today->toDateString())
            ->get()
            ->reject(fn (WastePickup $pickup): bool => isset($wanted[$pickup->waste_container_id.'|'.$pickup->due_on?->toDateString()]))
            ->each(fn (WastePickup $pickup) => $pickup->delete());
    }
}
