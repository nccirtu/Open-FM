<?php

namespace App\Services\Energy;

use App\Enums\EnergyMedium;
use App\Models\Meter;
use App\Models\Property;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class EnergyFlow
{
    public const UNASSIGNED = 'unassigned';

    public function __construct(private EnergyReport $report) {}

    /**
     * @return array{nodes: array<int, array{id: string, label: string, column: int, kind: string}>, links: array<int, array{source: string, target: string, value: float}>}
     */
    public function build(Property $property, EnergyMedium $medium, CarbonInterface $from, CarbonInterface $to): array
    {
        $meters = Meter::query()
            ->where('property_id', $property->getKey())
            ->where('medium', $medium->value)
            ->with('measuredAssets')
            ->get()
            ->filter(fn (Meter $meter): bool => ! $meter->isVirtual() || self::isRemainder($meter))
            ->values();

        $roots = $meters->filter(fn (Meter $meter): bool => $meter->parent_id === null || ! $meters->contains('id', $meter->parent_id));

        return $this->walk($meters, $roots, $from, $to);
    }

    /**
     * @return array{nodes: array<int, array{id: string, label: string, column: int, kind: string}>, links: array<int, array{source: string, target: string, value: float}>}
     */
    public function buildFor(Meter $root, CarbonInterface $from, CarbonInterface $to): array
    {
        $meters = Meter::query()
            ->where('property_id', $root->property_id)
            ->where('medium', $root->medium->value)
            ->whereKeyNot($root->getKey())
            ->with('measuredAssets')
            ->get()
            ->filter(fn (Meter $meter): bool => ! $meter->isVirtual() || self::isRemainder($meter))
            ->push($root->loadMissing('measuredAssets'))
            ->values();

        return $this->walk($meters, collect([$root]), $from, $to);
    }

    public static function isRemainder(Meter $meter): bool
    {
        return $meter->parent_id !== null && collect($meter->formula ?? [])
            ->contains(fn (mixed $term): bool => is_array($term)
                && ($term['meter_id'] ?? null) === $meter->parent_id
                && (int) ($term['sign'] ?? 1) > 0);
    }

    /**
     * @param  Collection<int, Meter>  $meters
     * @param  Collection<int, Meter>  $roots
     * @return array{nodes: array<int, array{id: string, label: string, column: int, kind: string}>, links: array<int, array{source: string, target: string, value: float}>}
     */
    private function walk(Collection $meters, Collection $roots, CarbonInterface $from, CarbonInterface $to): array
    {
        $byMeter = $this->report->consumptionByMeter($meters, $from, $to);
        $children = $meters->groupBy(fn (Meter $meter): string => (string) $meter->parent_id);
        $nodes = [];
        $links = [];
        $assetColumn = 0;

        $walk = function (Meter $meter, int $depth) use (&$walk, &$nodes, &$links, &$assetColumn, $children, $byMeter): void {
            $value = $byMeter[$meter->getKey()] ?? 0.0;
            $nodes[$meter->getKey()] = ['id' => $meter->getKey(), 'label' => $meter->name, 'column' => $depth, 'kind' => 'meter'];
            $assetColumn = max($assetColumn, $depth + 1);
            $passed = 0.0;

            foreach ($children->get($meter->getKey(), collect()) as $child) {
                $childValue = $byMeter[$child->getKey()] ?? 0.0;

                if ($childValue <= 0) {
                    continue;
                }

                $walk($child, $depth + 1);
                $links[] = ['source' => $meter->getKey(), 'target' => $child->getKey(), 'value' => $childValue];
                $passed += $childValue;
            }

            foreach ($meter->measuredAssets as $asset) {
                $assetValue = $value * (float) ($asset->pivot->share ?? 100) / 100;

                if ($assetValue <= 0) {
                    continue;
                }

                $id = 'asset:'.$asset->getKey();
                $nodes[$id] ??= ['id' => $id, 'label' => $asset->name, 'column' => -1, 'kind' => 'asset'];
                $links[] = ['source' => $meter->getKey(), 'target' => $id, 'value' => $assetValue];
                $passed += $assetValue;
            }

            $rest = $value - $passed;

            if ($rest > max(0.005 * $value, 0.01) && $passed > 0) {
                $nodes[self::UNASSIGNED] ??= ['id' => self::UNASSIGNED, 'label' => __('energy.flow.unassigned'), 'column' => -1, 'kind' => 'rest'];
                $links[] = ['source' => $meter->getKey(), 'target' => self::UNASSIGNED, 'value' => $rest];
            }
        };

        foreach ($roots as $root) {
            if (($byMeter[$root->getKey()] ?? 0.0) > 0) {
                $walk($root, 0);
            }
        }

        foreach ($nodes as $id => $node) {
            if ($node['column'] === -1) {
                $nodes[$id]['column'] = $assetColumn;
            }
        }

        return ['nodes' => array_values($nodes), 'links' => $links];
    }

    /**
     * @param  array{nodes: array<int, mixed>, links: array<int, mixed>}  $flow
     */
    public static function isEmpty(array $flow): bool
    {
        return $flow['nodes'] === [];
    }
}
