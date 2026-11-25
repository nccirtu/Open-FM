<?php

namespace App\Services\Waste;

use App\Enums\CostEntryKind;
use App\Models\CostEntry;
use App\Models\WasteCollection;
use App\Models\WasteContainer;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class WasteInvoiceCheck
{
    private const TOLERANCE_SHARE = 0.02;

    private const TOLERANCE_MIN = 1.0;

    /**
     * @return Collection<string, array{id: string, container: WasteContainer, pickups: int, tonnes: float, expected: float|null, actual: float, difference: float|null, status: string}>
     */
    public function rows(CarbonImmutable $month, ?string $propertyId = null): Collection
    {
        $from = $month->startOfMonth();
        $to = $month->endOfMonth();

        $containers = WasteContainer::query()
            ->with(['property', 'fraction', 'company'])
            ->when($propertyId !== null, fn (Builder $query) => $query->where('property_id', $propertyId))
            ->orderBy('number')
            ->get();

        $collections = WasteCollection::query()
            ->whereIn('waste_container_id', $containers->modelKeys())
            ->whereBetween('collected_at', [$from->startOfDay(), $to->endOfDay()])
            ->where(fn (Builder $query) => $query
                ->whereNull('waste_pickup_id')
                ->orWhereHas('pickup', fn (Builder $pickup) => $pickup->where('free_of_charge', false)))
            ->selectRaw('waste_container_id, COUNT(*) as pickups, SUM(mass_kg) as mass')
            ->groupBy('waste_container_id')
            ->get()
            ->keyBy('waste_container_id');

        $costs = CostEntry::query()
            ->where('costable_type', (new WasteContainer)->getMorphClass())
            ->whereIn('costable_id', $containers->modelKeys())
            ->where('kind', CostEntryKind::Actual->value)
            ->whereDate('booked_on', '>=', $from->toDateString())
            ->whereDate('booked_on', '<=', $to->toDateString())
            ->selectRaw('costable_id, SUM(net_amount) as total')
            ->groupBy('costable_id')
            ->pluck('total', 'costable_id');

        return $containers
            ->map(function (WasteContainer $container) use ($collections, $costs): array {
                $row = $collections->get($container->getKey());
                $pickups = (int) ($row?->getAttribute('pickups') ?? 0);
                $tonnes = (float) ($row?->getAttribute('mass') ?? 0) / 1000;
                $actual = (float) ($costs[$container->getKey()] ?? 0);
                $hasPrices = $container->price_per_pickup !== null || $container->price_per_tonne !== null || $container->monthly_rent !== null;
                $expected = $hasPrices
                    ? $pickups * (float) $container->price_per_pickup + $tonnes * (float) $container->price_per_tonne + (float) $container->monthly_rent
                    : null;

                return [
                    'id' => $container->getKey(),
                    'container' => $container,
                    'pickups' => $pickups,
                    'tonnes' => round($tonnes, 3),
                    'expected' => $expected !== null ? round($expected, 2) : null,
                    'actual' => round($actual, 2),
                    'difference' => $expected !== null ? round($actual - $expected, 2) : null,
                    'status' => $this->status($expected, $actual),
                ];
            })
            ->filter(fn (array $row): bool => $row['pickups'] > 0 || $row['actual'] > 0 || (float) $row['container']->monthly_rent > 0)
            ->keyBy('id');
    }

    public function status(?float $expected, float $actual): string
    {
        if ($expected === null) {
            return 'no_prices';
        }

        if ($actual <= 0 && $expected > 0) {
            return 'missing';
        }

        $difference = $actual - $expected;

        return match (true) {
            abs($difference) <= max(self::TOLERANCE_MIN, $expected * self::TOLERANCE_SHARE) => 'ok',
            $difference > 0 => 'over',
            default => 'under',
        };
    }
}
