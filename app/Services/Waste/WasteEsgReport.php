<?php

namespace App\Services\Waste;

use App\Enums\WasteEsrsCategory;
use App\Models\Property;
use App\Models\WasteFraction;
use App\Models\WasteTreatmentCategory;
use App\Support\WasteFilters;
use Carbon\CarbonImmutable;

final class WasteEsgReport
{
    public function __construct(private WasteReport $report) {}

    /**
     * @return array{
     *     year: int,
     *     kind: string,
     *     mass: array{total: float, hazardous: float, non_hazardous: float},
     *     construction_t: float,
     *     esrs: array<string, array{hazardous: float, non_hazardous: float, total: float}>,
     *     diverted: array{hazardous: float, non_hazardous: float, total: float},
     *     disposal: array{hazardous: float, non_hazardous: float, total: float},
     *     non_recycled: array{total: float, share: float|null},
     *     recycled_or_reused: float,
     *     diversion_rate: float|null,
     *     coverage: array{properties: int, properties_total: int, share: float|null, area_share: float|null},
     *     co2e: array{kg: float, coverage: float|null},
     * }
     */
    public function build(int $year, ?string $propertyId = null, ?CarbonImmutable $today = null, string $kind = WasteFilters::KIND_ALL): array
    {
        $today ??= CarbonImmutable::today();
        $from = CarbonImmutable::create($year)->startOfYear();
        $to = $from->endOfYear()->startOfDay();
        $filters = WasteFilters::fromArray([
            'period' => 'custom',
            'from' => $from->toDateString(),
            'until' => $to->toDateString(),
            'property_id' => $propertyId,
            'kind' => $kind,
        ], $today->greaterThan($to) ? $today : $to);

        $rows = $this->report->dailyValues($filters)
            ->selectRaw('property_id, waste_fraction_id, waste_treatment_category_id, SUM(mass_kg) as mass')
            ->groupBy('property_id', 'waste_fraction_id', 'waste_treatment_category_id')
            ->get();

        $hazardous = WasteFraction::query()->whereIn('id', $rows->pluck('waste_fraction_id')->unique())->pluck('is_hazardous', 'id');
        $esrs = WasteTreatmentCategory::query()->whereIn('id', $rows->pluck('waste_treatment_category_id')->filter()->unique())->pluck('esrs_category', 'id');

        $buckets = array_fill_keys([...array_map(fn (WasteEsrsCategory $category): string => $category->value, WasteEsrsCategory::cases()), 'unknown'], ['hazardous' => 0.0, 'non_hazardous' => 0.0, 'total' => 0.0]);

        foreach ($rows as $row) {
            $tonnes = (float) $row->getAttribute('mass') / 1000;
            $category = $row->waste_treatment_category_id !== null ? $esrs[$row->waste_treatment_category_id] ?? null : null;
            $key = $category instanceof WasteEsrsCategory ? $category->value : (WasteEsrsCategory::tryFrom((string) $category)?->value ?? 'unknown');
            $kind = ($hazardous[$row->waste_fraction_id] ?? false) ? 'hazardous' : 'non_hazardous';

            $buckets[$key][$kind] += $tonnes;
            $buckets[$key]['total'] += $tonnes;
        }

        $sum = fn (array $keys): array => [
            'hazardous' => round(array_sum(array_column(array_intersect_key($buckets, array_flip($keys)), 'hazardous')), 3),
            'non_hazardous' => round(array_sum(array_column(array_intersect_key($buckets, array_flip($keys)), 'non_hazardous')), 3),
            'total' => round(array_sum(array_column(array_intersect_key($buckets, array_flip($keys)), 'total')), 3),
        ];

        $all = $sum(array_keys($buckets));
        $diverted = $sum(['reuse', 'recycling', 'other_recovery']);
        $disposal = $sum(['incineration', 'landfill', 'other_disposal']);
        $recycledOrReused = $sum(['reuse', 'recycling'])['total'];

        return [
            'year' => $year,
            'kind' => $filters->kind(),
            'mass' => ['total' => $all['total'], 'hazardous' => $all['hazardous'], 'non_hazardous' => $all['non_hazardous']],
            'construction_t' => $filters->kind() === WasteFilters::KIND_OPERATIONS ? 0.0 : round($this->report->totalMass($filters->withKind(WasteFilters::KIND_CONSTRUCTION)) / 1000, 3),
            'esrs' => array_map(fn (array $bucket): array => array_map(fn (float $value): float => round($value, 3), $bucket), $buckets),
            'diverted' => $diverted,
            'disposal' => $disposal,
            'non_recycled' => [
                'total' => round($all['total'] - $recycledOrReused, 3),
                'share' => $all['total'] > 0 ? ($all['total'] - $recycledOrReused) / $all['total'] * 100 : null,
            ],
            'recycled_or_reused' => $recycledOrReused,
            'diversion_rate' => $all['total'] > 0 ? $recycledOrReused / $all['total'] * 100 : null,
            'coverage' => $this->coverage($filters, $rows->pluck('property_id')->unique()->all()),
            'co2e' => $this->report->co2e($filters),
        ];
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array<int, array{0: string, 1: string, 2: string, 3: float|int|string}>
     */
    public function csvRows(array $report): array
    {
        $rows = [
            ['ESRS E5-5', __('waste.esg.total_generated'), 't', $report['mass']['total']],
            ['ESRS E5-5', __('waste.esg.hazardous'), 't', $report['mass']['hazardous']],
            ['ESRS E5-5', __('waste.esg.non_hazardous'), 't', $report['mass']['non_hazardous']],
            ['ESRS E5-5', __('waste.esg.construction'), 't', $report['construction_t']],
            ['ESRS E5-5', __('waste.esg.diverted'), 't', $report['diverted']['total']],
        ];

        foreach (['reuse', 'recycling', 'other_recovery', 'incineration', 'landfill', 'other_disposal'] as $key) {
            foreach (['hazardous', 'non_hazardous'] as $kind) {
                $rows[] = ['ESRS E5-5', WasteEsrsCategory::from($key)->getLabel().' – '.__('waste.esg.'.$kind), 't', $report['esrs'][$key][$kind]];
            }
        }

        return [
            ...$rows,
            ['ESRS E5-5', __('waste.esg.disposal'), 't', $report['disposal']['total']],
            ['ESRS E5-5', __('waste.esg.unknown'), 't', $report['esrs']['unknown']['total']],
            ['ESRS E5-5', __('waste.esg.non_recycled'), 't', $report['non_recycled']['total']],
            ['ESRS E5-5', __('waste.esg.non_recycled_share'), '%', round((float) $report['non_recycled']['share'], 1)],
            ['VSME B7', __('waste.esg.total_generated'), 't', $report['mass']['total']],
            ['VSME B7', __('waste.esg.hazardous'), 't', $report['mass']['hazardous']],
            ['VSME B7', __('waste.esg.recycled_or_reused'), 't', $report['recycled_or_reused']],
            ['GRESB', __('waste.esg.coverage_properties'), '%', round((float) $report['coverage']['share'], 1)],
            ['GRESB', __('waste.esg.coverage_area'), '%', round((float) $report['coverage']['area_share'], 1)],
            ['LEED O+M', __('waste.esg.diversion_rate'), '%', round((float) $report['diversion_rate'], 1)],
            ['GHG', __('waste.stats.co2e'), 't CO2e', round($report['co2e']['kg'] / 1000, 2)],
        ];
    }

    /**
     * @param  array<int, string>  $withData
     * @return array{properties: int, properties_total: int, share: float|null, area_share: float|null}
     */
    private function coverage(WasteFilters $filters, array $withData): array
    {
        $properties = $filters->properties()->get();
        $area = $properties->sum(fn (Property $property): float => $property->referenceArea() ?? 0.0);
        $covered = $properties->whereIn('id', $withData);

        return [
            'properties' => $covered->count(),
            'properties_total' => $properties->count(),
            'share' => $properties->isNotEmpty() ? $covered->count() / $properties->count() * 100 : null,
            'area_share' => $area > 0 ? $covered->sum(fn (Property $property): float => $property->referenceArea() ?? 0.0) / $area * 100 : null,
        ];
    }
}
