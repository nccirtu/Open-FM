<?php

namespace App\Services\Waste;

use App\Enums\WasteCollectionSource;
use App\Enums\WasteMassSource;
use App\Enums\WastePickupStatus;
use App\Models\Property;
use App\Models\WasteCollection;
use App\Models\WasteContainer;
use App\Models\WasteFraction;
use App\Models\WastePickup;
use App\Models\WasteSettings;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

final class WasteCollectionRecorder
{
    private const MATCH_DAYS = 2;

    private const FALLBACK_DENSITY_KG_M3 = 100.0;

    private const EXTRA_ATTRIBUTES = ['receipt', 'note', 'document_number', 'certificate_number', 'volume_m3', 'project_id', 'asset_id', 'batteries_removed'];

    public function __construct(private WasteAggregator $aggregator) {}

    /**
     * @param  array{waste_treatment_id?: string|null, receipt?: string|null, note?: string|null, document_number?: string|null, certificate_number?: string|null, volume_m3?: float|null, project_id?: string|null, asset_id?: string|null, batteries_removed?: bool|null}  $attributes
     */
    public function recordForContainer(
        WasteContainer $container,
        CarbonInterface $collectedAt,
        WasteCollectionSource $source,
        ?float $massKg = null,
        ?int $fillLevelPct = null,
        array $attributes = [],
        ?WastePickup $pickup = null,
    ): WasteCollection {
        if ($massKg !== null && $massKg < 0) {
            throw new InvalidArgumentException(__('waste.errors.negative_mass'));
        }

        $collectedAt = CarbonImmutable::parse($collectedAt)->setMicrosecond(0);
        $pickup ??= $this->matchingPickup($container, $collectedAt);
        $fillLevelPct = $fillLevelPct !== null ? max(0, min(100, $fillLevelPct)) : null;

        [$mass, $massSource] = $massKg !== null
            ? [$massKg, WasteMassSource::Measured]
            : [$this->estimate($container, $fillLevelPct ?? $this->usualFillLevel($container)), WasteMassSource::Estimated];

        $collection = $pickup?->collection()->withoutGlobalScopes()->first() ?? new WasteCollection;
        $previous = $collection->exists ? [$collection->property_id, $collection->waste_fraction_id, $collection->collected_at] : null;

        $collection->fill([
            'waste_pickup_id' => $pickup?->getKey(),
            'waste_container_id' => $container->getKey(),
            'property_id' => $container->property_id,
            'waste_fraction_id' => $container->waste_fraction_id,
            'company_id' => $container->company_id,
            'collected_at' => $collectedAt,
            'mass_kg' => round($mass, 2),
            'mass_source' => $massSource,
            'fill_level_pct' => $fillLevelPct,
            'volume_l' => $container->volume_l,
            'waste_treatment_id' => $attributes['waste_treatment_id'] ?? $collection->waste_treatment_id ?? $this->fraction($container)?->waste_treatment_id,
            'source' => $source,
            ...$this->extraAttributes($attributes, $collection),
        ]);
        $collection->organization_id = $container->organization_id;
        $collection->created_by ??= auth()->id();
        $collection->save();

        if ($pickup !== null) {
            $this->confirmPickup($pickup, $source);
        }

        $this->refresh($collection, $previous);

        return $collection;
    }

    /**
     * @param  array{waste_treatment_id?: string|null, company_id?: string|null, receipt?: string|null, note?: string|null, document_number?: string|null, certificate_number?: string|null, volume_m3?: float|null, project_id?: string|null, asset_id?: string|null, batteries_removed?: bool|null}  $attributes
     */
    public function recordForProperty(
        Property $property,
        WasteFraction $fraction,
        CarbonInterface $collectedAt,
        float $massKg,
        WasteCollectionSource $source,
        array $attributes = [],
    ): WasteCollection {
        if ($massKg < 0) {
            throw new InvalidArgumentException(__('waste.errors.negative_mass'));
        }

        $collection = new WasteCollection([
            'property_id' => $property->getKey(),
            'waste_fraction_id' => $fraction->getKey(),
            'company_id' => $attributes['company_id'] ?? null,
            'collected_at' => CarbonImmutable::parse($collectedAt)->setMicrosecond(0),
            'mass_kg' => round($massKg, 2),
            'mass_source' => WasteMassSource::Measured,
            'waste_treatment_id' => $attributes['waste_treatment_id'] ?? $fraction->waste_treatment_id,
            'source' => $source,
            ...$this->extraAttributes($attributes),
        ]);
        $collection->organization_id = $property->organization_id;
        $collection->created_by = auth()->id();
        $collection->save();

        $this->refresh($collection, null);

        return $collection;
    }

    public function completeByPlan(WastePickup $pickup): WasteCollection
    {
        $container = $pickup->container()->withoutGlobalScopes()->firstOrFail();
        $collectedAt = CarbonImmutable::parse($pickup->due_on ?? now())->setTime(12, 0);

        $collection = $pickup->collection()->withoutGlobalScopes()->first() ?? new WasteCollection;
        $previous = $collection->exists ? [$collection->property_id, $collection->waste_fraction_id, $collection->collected_at] : null;

        $collection->fill([
            'waste_pickup_id' => $pickup->getKey(),
            'waste_container_id' => $container->getKey(),
            'property_id' => $container->property_id,
            'waste_fraction_id' => $container->waste_fraction_id,
            'company_id' => $container->company_id,
            'collected_at' => $collectedAt,
            'mass_kg' => round($this->estimate($container, $this->usualFillLevel($container)), 2),
            'mass_source' => WasteMassSource::Estimated,
            'fill_level_pct' => null,
            'volume_l' => $container->volume_l,
            'waste_treatment_id' => $this->fraction($container)?->waste_treatment_id,
            'source' => WasteCollectionSource::Schedule,
        ]);
        $collection->organization_id = $container->organization_id;
        $collection->save();

        $pickup->forceFill([
            'status' => WastePickupStatus::Confirmed,
            'confirmed_at' => now(),
            'confirmed_via' => WasteCollectionSource::Schedule,
            'confirmed_by' => null,
        ])->save();

        $this->refresh($collection, $previous);

        return $collection;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(WasteCollection $collection, CarbonInterface $collectedAt, ?float $massKg, ?int $fillLevelPct, array $attributes = []): WasteCollection
    {
        if ($massKg !== null && $massKg < 0) {
            throw new InvalidArgumentException(__('waste.errors.negative_mass'));
        }

        $previous = [$collection->property_id, $collection->waste_fraction_id, $collection->collected_at];
        $container = $collection->waste_container_id !== null
            ? WasteContainer::query()->withoutGlobalScopes()->find($collection->waste_container_id)
            : null;

        if ($massKg !== null) {
            $collection->mass_kg = round($massKg, 2);
            $collection->mass_source = WasteMassSource::Measured;
        } elseif ($fillLevelPct !== null && $container !== null) {
            $collection->mass_kg = round($this->estimate($container, max(0, min(100, $fillLevelPct))), 2);
            $collection->mass_source = WasteMassSource::Estimated;
        }

        $collection->fill([
            'collected_at' => CarbonImmutable::parse($collectedAt)->setMicrosecond(0),
            'fill_level_pct' => $fillLevelPct ?? $collection->fill_level_pct,
            ...array_intersect_key($attributes, array_flip(['waste_treatment_id', ...self::EXTRA_ATTRIBUTES])),
        ]);
        $collection->save();

        $this->refresh($collection, $previous);

        return $collection;
    }

    public function delete(WasteCollection $collection): void
    {
        $previous = [$collection->property_id, $collection->waste_fraction_id, $collection->collected_at];

        $collection->delete();

        $this->aggregator->refresh(...$previous);
    }

    public function usualFillLevel(WasteContainer $container): int
    {
        if ($container->usual_fill_pct !== null) {
            return $container->usual_fill_pct;
        }

        $recent = WasteCollection::query()
            ->withoutGlobalScopes()
            ->where('waste_container_id', $container->getKey())
            ->whereNotNull('fill_level_pct')
            ->latest('collected_at')
            ->limit(8)
            ->pluck('fill_level_pct');

        if ($recent->isNotEmpty()) {
            return (int) round((float) $recent->median());
        }

        return WasteSettings::forOrganization($container->organization_id)->default_fill_pct;
    }

    public function estimate(WasteContainer $container, int $fillLevelPct): float
    {
        $density = (float) ($this->fraction($container)?->density_kg_m3 ?: self::FALLBACK_DENSITY_KG_M3);

        return $container->volume_l / 1000 * ($fillLevelPct / 100) * $density;
    }

    private function matchingPickup(WasteContainer $container, CarbonImmutable $collectedAt): ?WastePickup
    {
        return WastePickup::query()
            ->withoutGlobalScopes()
            ->where('waste_container_id', $container->getKey())
            ->where(fn ($query) => $query
                ->whereIn('status', [WastePickupStatus::Planned->value, WastePickupStatus::Requested->value])
                ->orWhere(fn ($query) => $query
                    ->where('status', WastePickupStatus::Confirmed->value)
                    ->where('confirmed_via', WasteCollectionSource::Schedule->value)))
            ->whereBetween('due_on', [
                $collectedAt->subDays(self::MATCH_DAYS)->toDateString(),
                $collectedAt->addDays(self::MATCH_DAYS)->toDateString(),
            ])
            ->get()
            ->sortBy(fn (WastePickup $pickup): float => abs($pickup->due_on->diffInDays($collectedAt->startOfDay())))
            ->first();
    }

    private function confirmPickup(WastePickup $pickup, WasteCollectionSource $source): void
    {
        $pickup->forceFill([
            'status' => WastePickupStatus::Confirmed,
            'confirmed_at' => now(),
            'confirmed_via' => $source,
            'confirmed_by' => auth()->id(),
        ])->save();
    }

    /**
     * @param  array{0: string, 1: string, 2: CarbonInterface}|null  $previous
     */
    private function refresh(WasteCollection $collection, ?array $previous): void
    {
        $this->aggregator->refresh($collection->property_id, $collection->waste_fraction_id, $collection->collected_at);

        if ($previous !== null
            && ($previous[0] !== $collection->property_id
                || $previous[1] !== $collection->waste_fraction_id
                || ! CarbonImmutable::parse($previous[2])->isSameDay($collection->collected_at))) {
            $this->aggregator->refresh(...$previous);
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function extraAttributes(array $attributes, ?WasteCollection $existing = null): array
    {
        $values = [];

        foreach (self::EXTRA_ATTRIBUTES as $key) {
            $values[$key] = array_key_exists($key, $attributes) && $attributes[$key] !== null
                ? $attributes[$key]
                : $existing?->getAttribute($key);
        }

        return $values;
    }

    private function fraction(WasteContainer $container): ?WasteFraction
    {
        return $container->relationLoaded('fraction')
            ? $container->fraction
            : WasteFraction::query()->withoutGlobalScopes()->find($container->waste_fraction_id);
    }
}
