<?php

namespace App\Services\Waste;

use App\Enums\WasteEvidenceStatus;
use App\Models\Company;
use App\Models\Property;
use App\Models\WasteCollection;
use App\Models\WasteContainer;
use App\Models\WasteEvidence;
use App\Models\WasteFraction;
use App\Models\WasteSettings;
use App\Support\WasteFilters;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class WasteDocumentation
{
    public function __construct(private WasteReport $report) {}

    /**
     * @return array{
     *     property: Property,
     *     year: int,
     *     separation: array{rate: float|null, target: float|null, met: bool|null, due: CarbonImmutable|null},
     *     mass_t: float,
     *     mixed: Collection<int, array{fraction: WasteFraction, mass: float}>,
     *     fractions: Collection<int, array{fraction: WasteFraction, mass: float}>,
     *     items: array<int, array{type: string, label: string, required: bool, status: WasteEvidenceStatus, evidence: WasteEvidence|null, hint: string, basis: string, url: string}>,
     *     complete: bool,
     * }
     */
    public function for(Property $property, int $year, ?CarbonImmutable $today = null): array
    {
        $today ??= CarbonImmutable::today();
        $settings = WasteSettings::forOrganization($property->organization_id);
        $from = CarbonImmutable::create($year)->startOfYear();
        $to = $from->endOfYear()->startOfDay();
        $filters = WasteFilters::fromArray([
            'period' => 'custom',
            'from' => $from->toDateString(),
            'until' => $to->toDateString(),
            'property_id' => $property->getKey(),
        ], $today->greaterThan($to) ? $today : $to);

        $fractions = $today->lessThan($from) ? collect() : $this->report->massByFraction($filters);
        $mixed = $fractions->filter(fn (array $row): bool => $row['fraction']->group !== null && ! $row['fraction']->group->counts_as_separated)->values();
        $rate = $today->lessThan($from) ? null : $this->report->separationRate($filters);
        $target = $settings->rule('separation_quota_pct', 90);
        $target = $target !== null ? (float) $target : null;
        $due = $settings->rule('quota_check_due', '03-31');
        [$dueMonth, $dueDay] = $due !== null ? array_map('intval', explode('-', (string) $due) + [1 => 31]) : [null, null];

        $evidence = WasteEvidence::query()
            ->withoutGlobalScopes()
            ->where('property_id', $property->getKey())
            ->where('year', $year)
            ->get()
            ->keyBy('type');

        $hints = $this->hints($property, $from, $to, $mixed);
        $context = $this->context($property, $from, $to, $mixed);

        $items = collect($settings->evidenceRequirements())->map(function (array $requirement, string $type) use ($evidence, $hints, $context): array {
            [$required, $conditionHint] = $this->requirement($requirement['when'], $context);
            $record = $evidence->get($type);

            return [
                'type' => $type,
                'label' => WasteEvidence::labelFor($type),
                'required' => $required,
                'status' => $record?->status ?? ($required ? WasteEvidenceStatus::Open : WasteEvidenceStatus::NotRequired),
                'evidence' => $record,
                'hint' => $required ? ($hints[$type] ?? $conditionHint) : $conditionHint,
                'basis' => $requirement['basis'],
                'url' => $requirement['url'],
            ];
        })->values()->all();

        return [
            'property' => $property,
            'year' => $year,
            'separation' => [
                'rate' => $rate,
                'target' => $target,
                'met' => $rate !== null && $target !== null ? $rate >= $target : null,
                'due' => $dueMonth !== null ? CarbonImmutable::create($year + 1, $dueMonth, $dueDay) : null,
            ],
            'mass_t' => $fractions->sum('mass') / 1000,
            'mixed' => $mixed,
            'fractions' => $fractions,
            'items' => $items,
            'complete' => collect($items)->every(fn (array $item): bool => ! $item['required'] || $item['status'] === WasteEvidenceStatus::Available),
        ];
    }

    /**
     * @param  Collection<int, array{fraction: WasteFraction, mass: float}>  $mixed
     * @return array{mixed_t: float, hazardous_t: float, construction_t: float, construction_year: int|null}
     */
    private function context(Property $property, CarbonImmutable $from, CarbonImmutable $to, Collection $mixed): array
    {
        $collections = WasteCollection::query()
            ->withoutGlobalScopes()
            ->where('property_id', $property->getKey())
            ->whereBetween('collected_at', [$from->startOfDay(), $to->endOfDay()]);

        return [
            'mixed_t' => $mixed->sum('mass') / 1000,
            'hazardous_t' => (float) (clone $collections)->whereHas('fraction', fn ($query) => $query->withoutGlobalScopes()->where('is_hazardous', true))->sum('mass_kg') / 1000,
            'construction_t' => (float) (clone $collections)->whereNotNull('project_id')->sum('mass_kg') / 1000,
            'construction_year' => $property->construction_year,
        ];
    }

    /**
     * @param  array{mixed_t: float, hazardous_t: float, construction_t: float, construction_year: int|null}  $context
     * @return array{0: bool, 1: string}
     */
    private function requirement(string $when, array $context): array
    {
        $tonnes = fn (float $value): string => number_format($value, 2, ',', '.');

        if (preg_match('/^built_before_(\d{4})$/', $when, $match) === 1) {
            $limit = (int) $match[1];

            return match (true) {
                $context['construction_year'] === null => [true, __('waste.documentation.when.year_missing', ['limit' => $limit])],
                $context['construction_year'] < $limit => [true, __('waste.documentation.when.built_before', ['year' => $context['construction_year'], 'limit' => $limit])],
                default => [false, __('waste.documentation.when.built_after', ['year' => $context['construction_year'], 'limit' => $limit])],
            };
        }

        return match ($when) {
            'mixed' => $context['mixed_t'] > 0 ? [true, __('waste.documentation.when.mixed', ['mass' => $tonnes($context['mixed_t'])])] : [false, __('waste.documentation.hints.no_mixed')],
            'hazardous' => $context['hazardous_t'] > 0 ? [true, __('waste.documentation.when.hazardous', ['mass' => $tonnes($context['hazardous_t'])])] : [false, __('waste.documentation.when.no_hazardous')],
            'construction' => $context['construction_t'] > 0 ? [true, __('waste.documentation.when.construction', ['mass' => $tonnes($context['construction_t'])])] : [false, __('waste.documentation.when.no_construction')],
            'conditional' => [false, __('waste.documentation.when.conditional')],
            default => [true, ''],
        };
    }

    /**
     * @param  Collection<int, array{fraction: WasteFraction, mass: float}>  $mixed
     * @return array<string, string>
     */
    private function hints(Property $property, CarbonImmutable $from, CarbonImmutable $to, Collection $mixed): array
    {
        $containers = WasteContainer::query()->withoutGlobalScopes()->where('property_id', $property->getKey())->where('is_active', true);
        $collections = WasteCollection::query()
            ->withoutGlobalScopes()
            ->where('property_id', $property->getKey())
            ->whereNull('project_id')
            ->whereBetween('collected_at', [$from->startOfDay(), $to->endOfDay()]);
        $companies = Company::query()
            ->withoutGlobalScopes()
            ->whereIn('id', (clone $containers)->whereNotNull('company_id')->select('company_id'))
            ->orderBy('name')
            ->pluck('name');
        $mixedText = $mixed->map(fn (array $row): string => $row['fraction']->name.' ('.number_format($row['mass'] / 1000, 2, ',', '.').' t)')->implode(', ');

        return [
            'site_plan' => __('waste.documentation.hints.site_plan', [
                'points' => (clone $containers)->whereNotNull('structure_node_id')->distinct()->count('structure_node_id'),
                'containers' => (clone $containers)->count(),
            ]),
            'practice_records' => __('waste.documentation.hints.practice_records', [
                'collections' => (clone $collections)->count(),
                'receipts' => (clone $collections)->where(fn ($query) => $query->whereNotNull('receipt')->orWhereNotNull('document_number'))->count(),
            ]),
            'acceptor_declaration' => $companies->isEmpty()
                ? __('waste.documentation.hints.no_company')
                : __('waste.documentation.hints.companies', ['companies' => $companies->implode(', ')]),
            'pretreatment_confirmation' => $mixed->isEmpty() ? __('waste.documentation.hints.no_mixed') : __('waste.documentation.hints.mixed', ['fractions' => $mixedText]),
            'exception_reason' => $mixed->isEmpty() ? __('waste.documentation.hints.no_mixed') : __('waste.documentation.hints.exception'),
        ];
    }
}
