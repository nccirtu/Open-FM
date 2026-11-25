<?php

namespace App\Services\Waste;

use App\Enums\CostEntryKind;
use App\Enums\StructureNodeType;
use App\Models\CostEntry;
use App\Models\Property;
use App\Models\StructureNode;
use App\Models\WasteContainer;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class WasteCostAllocation
{
    public const METHODS = ['area', 'equal'];

    /**
     * @return array{total: float, area: float, method: string, rows: Collection<string, array{id: string, unit: StructureNode, area: float|null, share: float, amount: float}>}
     */
    public function allocate(Property $property, CarbonImmutable $from, CarbonImmutable $to, string $method = 'area'): array
    {
        $method = in_array($method, self::METHODS, true) ? $method : 'area';

        $total = (float) CostEntry::query()
            ->where('costable_type', (new WasteContainer)->getMorphClass())
            ->whereIn('costable_id', WasteContainer::query()->where('property_id', $property->getKey())->select('id'))
            ->where('kind', CostEntryKind::Actual->value)
            ->whereDate('booked_on', '>=', $from->toDateString())
            ->whereDate('booked_on', '<=', $to->toDateString())
            ->sum('net_amount');

        $units = StructureNode::query()
            ->where('property_id', $property->getKey())
            ->where('type', StructureNodeType::Unit->value)
            ->with('descendants')
            ->orderBy('sort')
            ->orderBy('name')
            ->get();

        $areas = $units->mapWithKeys(fn (StructureNode $unit): array => [$unit->getKey() => $this->area($unit)]);
        $areaSum = (float) $areas->sum();

        $rows = $units->mapWithKeys(function (StructureNode $unit) use ($method, $areas, $areaSum, $units, $total): array {
            $share = match (true) {
                $method === 'equal' => 1 / max(1, $units->count()),
                $areaSum > 0 => (float) ($areas[$unit->getKey()] ?? 0) / $areaSum,
                default => 0.0,
            };

            return [$unit->getKey() => [
                'id' => $unit->getKey(),
                'unit' => $unit,
                'area' => $areas[$unit->getKey()],
                'share' => $share * 100,
                'amount' => round($total * $share, 2),
            ]];
        });

        return ['total' => round($total, 2), 'area' => $areaSum, 'method' => $method, 'rows' => $rows];
    }

    private function area(StructureNode $unit): ?float
    {
        if ((float) $unit->area > 0) {
            return (float) $unit->area;
        }

        $rooms = (float) $unit->descendants->where('type', StructureNodeType::Room)->sum('area');

        return $rooms > 0 ? $rooms : null;
    }
}
