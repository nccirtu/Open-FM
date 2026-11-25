<?php

namespace App\Services\Waste;

use App\Enums\WasteCollectionSource;
use App\Enums\WastePickupStatus;
use App\Models\WasteCollection;
use App\Models\WasteContainer;
use App\Models\WasteFillReading;
use App\Models\WastePickup;
use App\Models\WasteSettings;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

final class WasteSensorService
{
    private const PICKUP_DROP_FROM = 30;

    private const PICKUP_DROP_TO = 10;

    public function __construct(
        private WasteCollectionRecorder $recorder,
        private PickupService $pickups,
    ) {}

    /**
     * @return array{reading: WasteFillReading, collection: WasteCollection|null, request: WastePickup|null}
     */
    public function record(
        WasteContainer $container,
        CarbonInterface $measuredAt,
        int $fillLevelPct,
        ?float $temperature = null,
        ?string $sensorId = null,
        bool $pickupDetected = false,
    ): array {
        $measuredAt = CarbonImmutable::parse($measuredAt)->setMicrosecond(0);
        $fillLevelPct = max(0, min(100, $fillLevelPct));

        $previous = WasteFillReading::query()
            ->withoutGlobalScopes()
            ->where('waste_container_id', $container->getKey())
            ->where('measured_at', '<', $measuredAt)
            ->latest('measured_at')
            ->first();

        $detected = $pickupDetected
            || ($previous !== null && $previous->fill_level_pct >= self::PICKUP_DROP_FROM && $fillLevelPct <= self::PICKUP_DROP_TO);

        $reading = new WasteFillReading([
            'waste_container_id' => $container->getKey(),
            'measured_at' => $measuredAt,
            'fill_level_pct' => $fillLevelPct,
            'temperature_c' => $temperature,
            'sensor_id' => $sensorId,
            'pickup_detected' => $detected,
        ]);
        $reading->organization_id = $container->organization_id;
        $reading->save();

        $collection = $detected
            ? $this->recorder->recordForContainer($container, $measuredAt, WasteCollectionSource::Sensor, fillLevelPct: $previous?->fill_level_pct)
            : null;

        $request = ! $detected && $fillLevelPct >= WasteSettings::forOrganization($container->organization_id)->sensor_request_pct
            ? $this->requestIfNothingPlanned($container, $measuredAt)
            : null;

        return ['reading' => $reading, 'collection' => $collection, 'request' => $request];
    }

    private function requestIfNothingPlanned(WasteContainer $container, CarbonImmutable $measuredAt): ?WastePickup
    {
        $planned = WastePickup::query()
            ->withoutGlobalScopes()
            ->where('waste_container_id', $container->getKey())
            ->where(fn ($query) => $query
                ->where('status', WastePickupStatus::Requested->value)
                ->orWhere(fn ($query) => $query
                    ->where('status', WastePickupStatus::Planned->value)
                    ->whereBetween('due_on', [$measuredAt->toDateString(), $measuredAt->addDays(2)->toDateString()])))
            ->exists();

        return $planned ? null : $this->pickups->request($container);
    }
}
