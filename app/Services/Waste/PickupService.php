<?php

namespace App\Services\Waste;

use App\Enums\WasteCollectionSource;
use App\Enums\WasteMissedReason;
use App\Enums\WastePickupKind;
use App\Enums\WastePickupStatus;
use App\Models\WasteCollection;
use App\Models\WasteContainer;
use App\Models\WastePickup;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

final class PickupService
{
    public function __construct(
        private WasteCollectionRecorder $recorder,
        private PickupNotifier $notifier,
    ) {}

    /**
     * @param  array{waste_treatment_id?: string|null, receipt?: string|null, note?: string|null}  $attributes
     */
    public function confirm(
        WastePickup $pickup,
        WasteCollectionSource $via,
        ?int $fillLevelPct = null,
        ?float $massKg = null,
        array $attributes = [],
    ): WasteCollection {
        $container = $pickup->container()->withoutGlobalScopes()->firstOrFail();

        return $this->recorder->recordForContainer(
            $container,
            $this->collectedAt($pickup),
            $via,
            $massKg,
            $fillLevelPct,
            $attributes,
            $pickup,
        );
    }

    public function markMissed(WastePickup $pickup, WasteMissedReason $reason, ?string $note = null): ?WastePickup
    {
        $collection = $pickup->collection()->withoutGlobalScopes()->first();

        if ($collection !== null) {
            $this->recorder->delete($collection);
        }

        $pickup->forceFill([
            'status' => WastePickupStatus::Missed,
            'missed_reason' => $reason,
            'missed_note' => $note,
        ])->save();

        if (! $reason->isDisposerFault()) {
            return null;
        }

        $complaint = $this->notifier->complain($pickup, $reason, $note);

        if ($complaint !== null) {
            $pickup->forceFill(['ticket_id' => $complaint->getKey()])->save();
        }

        $container = $pickup->container()->withoutGlobalScopes()->first();

        if ($container === null) {
            return null;
        }

        $extra = $this->request($container, WastePickupKind::Extra);
        $extra->forceFill(['free_of_charge' => true])->save();

        return $extra;
    }

    public function request(WasteContainer $container, WastePickupKind $kind = WastePickupKind::OnDemand): WastePickup
    {
        $open = WastePickup::query()
            ->withoutGlobalScopes()
            ->where('waste_container_id', $container->getKey())
            ->where('status', WastePickupStatus::Requested->value)
            ->first();

        if ($open !== null) {
            return $open;
        }

        $pickup = new WastePickup([
            'waste_container_id' => $container->getKey(),
            'property_id' => $container->property_id,
            'kind' => $kind,
            'status' => WastePickupStatus::Requested,
            'requested_at' => now(),
        ]);
        $pickup->organization_id = $container->organization_id;
        $pickup->save();

        return $pickup;
    }

    public function scheduleExtra(WasteContainer $container, CarbonInterface $date): WastePickup
    {
        $pickup = new WastePickup([
            'waste_container_id' => $container->getKey(),
            'property_id' => $container->property_id,
            'due_on' => CarbonImmutable::parse($date)->toDateString(),
            'kind' => WastePickupKind::Extra,
            'status' => WastePickupStatus::Planned,
        ]);
        $pickup->organization_id = $container->organization_id;
        $pickup->save();

        return $pickup;
    }

    public function plan(WastePickup $pickup, CarbonInterface $date): WastePickup
    {
        $pickup->forceFill([
            'due_on' => CarbonImmutable::parse($date)->toDateString(),
            'status' => WastePickupStatus::Planned,
        ])->save();

        return $pickup;
    }

    public function cancel(WastePickup $pickup): void
    {
        $collection = $pickup->collection()->withoutGlobalScopes()->first();

        if ($collection !== null) {
            $this->recorder->delete($collection);
        }

        $pickup->forceFill(['status' => WastePickupStatus::Cancelled])->save();
    }

    private function collectedAt(WastePickup $pickup): CarbonImmutable
    {
        if ($pickup->due_on === null) {
            return CarbonImmutable::now();
        }

        $windowFrom = $pickup->schedule()->withoutGlobalScopes()->value('window_from');
        [$hour, $minute] = $windowFrom !== null ? array_map('intval', explode(':', (string) $windowFrom)) : [12, 0];

        return CarbonImmutable::parse($pickup->due_on)->setTime($hour, $minute);
    }
}
