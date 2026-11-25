<?php

namespace App\Services\Waste;

use App\Enums\WasteIntervalUnit;
use App\Enums\WastePickupKind;
use App\Enums\WasteScheduleSource;
use App\Models\WasteCollection;
use App\Models\WasteContainer;
use App\Models\WastePickup;
use App\Models\WasteSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class WasteRightsizing
{
    public const DAYS = 90;

    public const MIN_SAMPLES = 3;

    public const LOW = 50;

    public const HIGH = 90;

    public const SIZES = [60, 80, 120, 240, 360, 660, 770, 1100];

    /**
     * @return Collection<string, array{id: string, container: WasteContainer, pickups: int, extra: int, samples: int, average_fill: float|null, recommendation: string, suggested_volume: int|null, saving: float|null}>
     */
    public function recommendations(?string $propertyId = null, ?CarbonImmutable $today = null): Collection
    {
        $today ??= CarbonImmutable::today();
        $from = $today->subDays(self::DAYS);

        $containers = WasteContainer::query()
            ->active()
            ->with(['property', 'fraction', 'schedules', 'type'])
            ->when($propertyId !== null, fn (Builder $query) => $query->where('property_id', $propertyId))
            ->orderBy('number')
            ->get();

        $collections = WasteCollection::query()
            ->whereIn('waste_container_id', $containers->modelKeys())
            ->where('collected_at', '>=', $from->startOfDay())
            ->get(['waste_container_id', 'fill_level_pct', 'mass_kg', 'mass_source', 'volume_l'])
            ->groupBy('waste_container_id');

        $extras = WastePickup::query()
            ->whereIn('waste_container_id', $containers->modelKeys())
            ->whereIn('kind', [WastePickupKind::Extra->value, WastePickupKind::OnDemand->value])
            ->where('free_of_charge', false)
            ->where(fn (Builder $query) => $query->where('due_on', '>=', $from->toDateString())->orWhere('requested_at', '>=', $from->startOfDay()))
            ->selectRaw('waste_container_id, kind, COUNT(*) as total')
            ->groupBy('waste_container_id', 'kind')
            ->get()
            ->groupBy('waste_container_id');

        return $containers
            ->map(function (WasteContainer $container) use ($collections, $extras): array {
                $rows = $collections->get($container->getKey(), collect());
                $fills = $rows->map(fn (WasteCollection $collection): ?float => $this->fillLevel($container, $collection))->filter(fn (?float $fill): bool => $fill !== null);
                $average = $fills->count() >= self::MIN_SAMPLES ? $fills->avg() : null;
                $extra = (int) $extras->get($container->getKey(), collect())
                    ->filter(fn (WastePickup $row): bool => $row->kind === WastePickupKind::Extra || $this->hasRhythm($container))
                    ->sum(fn (WastePickup $row): int => (int) $row->getAttribute('total'));

                $recommendation = match (true) {
                    $extra > 0 || ($average !== null && $average > self::HIGH) => 'increase',
                    $average === null => 'no_data',
                    $average < self::LOW => 'reduce',
                    default => 'ok',
                };

                return [
                    'id' => $container->getKey(),
                    'container' => $container,
                    'pickups' => $rows->count(),
                    'extra' => $extra,
                    'samples' => $fills->count(),
                    'average_fill' => $average !== null ? round($average, 0) : null,
                    'recommendation' => $recommendation,
                    'suggested_volume' => $this->suggestedVolume($container, $recommendation, $average),
                    'saving' => $recommendation === 'reduce' && $container->price_per_pickup !== null && $this->canReduceRhythm($container)
                        ? round($rows->count() / 2 * (365 / self::DAYS) * (float) $container->price_per_pickup, 0)
                        : null,
                ];
            })
            ->keyBy('id');
    }

    public function fillLevel(WasteContainer $container, WasteCollection $collection): ?float
    {
        if ($collection->fill_level_pct !== null) {
            return (float) $collection->fill_level_pct;
        }

        if ($container->type?->key === 'compactor') {
            return null;
        }

        $density = (float) ($container->fraction?->density_kg_m3 ?? 0);
        $volume = (float) ($collection->volume_l ?: $container->volume_l);

        if ($collection->mass_source?->value !== 'measured' || $density <= 0 || $volume <= 0) {
            return null;
        }

        return min(100.0, (float) $collection->mass_kg / ($volume / 1000 * $density) * 100);
    }

    private function canReduceRhythm(WasteContainer $container): bool
    {
        return $container->schedules->contains(fn (WasteSchedule $schedule): bool => $schedule->source === WasteScheduleSource::Rule
            && in_array($schedule->interval_unit, [WasteIntervalUnit::Week, WasteIntervalUnit::Month], true));
    }

    private function hasRhythm(WasteContainer $container): bool
    {
        return $container->schedules->contains(fn (WasteSchedule $schedule): bool => $schedule->is_active && $schedule->source !== WasteScheduleSource::OnDemand);
    }

    private function suggestedVolume(WasteContainer $container, string $recommendation, ?float $average): ?int
    {
        if (! in_array((int) $container->volume_l, self::SIZES, true) || $average === null) {
            return null;
        }

        $needed = $container->volume_l * $average / 100 / 0.85;

        return match ($recommendation) {
            'reduce' => collect(self::SIZES)->first(fn (int $size): bool => $size >= $needed && $size < $container->volume_l),
            'increase' => collect(self::SIZES)->first(fn (int $size): bool => $size > $container->volume_l),
            default => null,
        };
    }
}
