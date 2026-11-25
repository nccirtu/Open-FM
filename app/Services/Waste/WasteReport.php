<?php

namespace App\Services\Waste;

use App\Enums\WasteMassSource;
use App\Enums\WastePickupStatus;
use App\Models\CostEntry;
use App\Models\WasteCollection;
use App\Models\WasteContainer;
use App\Models\WasteDailyValue;
use App\Models\WasteEmissionFactor;
use App\Models\WasteFraction;
use App\Models\WastePickup;
use App\Models\WasteTreatmentCategory;
use App\Support\WasteFilters;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class WasteReport
{
    /**
     * @return Builder<WasteDailyValue>
     */
    public function dailyValues(WasteFilters $filters, ?CarbonImmutable $from = null, ?CarbonImmutable $to = null): Builder
    {
        return $filters->scopeKind($filters->scope(WasteDailyValue::query()))
            ->whereBetween('date', [($from ?? $filters->from())->toDateString(), ($to ?? $filters->to())->toDateString()]);
    }

    public function totalMass(WasteFilters $filters, ?CarbonImmutable $from = null, ?CarbonImmutable $to = null): float
    {
        return (float) $this->dailyValues($filters, $from, $to)->sum('mass_kg');
    }

    /**
     * @return array<string, float>
     */
    public function massByTreatmentCategory(WasteFilters $filters): array
    {
        return $this->dailyValues($filters)
            ->selectRaw('waste_treatment_category_id, SUM(mass_kg) as mass')
            ->groupBy('waste_treatment_category_id')
            ->get()
            ->mapWithKeys(fn (WasteDailyValue $row): array => [(string) $row->waste_treatment_category_id => (float) $row->getAttribute('mass')])
            ->all();
    }

    public function recyclingRate(WasteFilters $filters): ?float
    {
        $byCategory = $this->massByTreatmentCategory($filters);
        $total = array_sum($byCategory);

        if ($total <= 0) {
            return null;
        }

        $recyclingIds = WasteTreatmentCategory::query()->where('counts_as_recycling', true)->pluck('id')->map(fn (mixed $id): string => (string) $id)->all();

        return collect($byCategory)->only($recyclingIds)->sum() / $total * 100;
    }

    public function separationRate(WasteFilters $filters): ?float
    {
        $byFraction = $this->massByFraction($filters);
        $total = $byFraction->sum('mass');

        if ($total <= 0) {
            return null;
        }

        $separated = $byFraction
            ->filter(fn (array $row): bool => $row['fraction']->group?->counts_as_separated ?? true)
            ->sum('mass');

        return $separated / $total * 100;
    }

    /**
     * @return Collection<int, array{fraction: WasteFraction, mass: float}>
     */
    public function massByFraction(WasteFilters $filters): Collection
    {
        $masses = $this->dailyValues($filters)
            ->selectRaw('waste_fraction_id, SUM(mass_kg) as mass')
            ->groupBy('waste_fraction_id')
            ->pluck('mass', 'waste_fraction_id');

        return WasteFraction::query()
            ->with('group')
            ->whereIn('id', $masses->keys())
            ->orderBy('sort')
            ->orderBy('name')
            ->get()
            ->map(fn (WasteFraction $fraction): array => ['fraction' => $fraction, 'mass' => (float) $masses[$fraction->getKey()]])
            ->sortByDesc('mass')
            ->values();
    }

    /**
     * @return array{labels: array<int, string>, datasets: array<int, array{label: string, color: string, data: array<int, float>}>}
     */
    public function massByFractionOverTime(WasteFilters $filters): array
    {
        $months = $filters->usesMonths();
        $buckets = [];

        for ($day = $filters->from(); $day->lessThanOrEqualTo($filters->to()); $day = $months ? $day->startOfMonth()->addMonth() : $day->addDay()) {
            $buckets[$months ? $day->format('Y-m') : $day->toDateString()] = $months
                ? $day->locale(app()->getLocale())->isoFormat('MMM YY')
                : $day->format('d.m.');
        }

        $rows = $this->dailyValues($filters)->get(['waste_fraction_id', 'date', 'mass_kg']);
        $fractions = WasteFraction::query()->whereIn('id', $rows->pluck('waste_fraction_id')->unique())->orderBy('sort')->orderBy('name')->get();

        $datasets = $fractions->map(function (WasteFraction $fraction) use ($rows, $buckets, $months): array {
            $sums = array_fill_keys(array_keys($buckets), 0.0);

            foreach ($rows->where('waste_fraction_id', $fraction->getKey()) as $row) {
                $key = $months ? $row->date->format('Y-m') : $row->date->toDateString();

                if (array_key_exists($key, $sums)) {
                    $sums[$key] += (float) $row->mass_kg / 1000;
                }
            }

            return [
                'label' => $fraction->name,
                'color' => $fraction->displayColor(),
                'data' => array_map(fn (float $value): float => round($value, 2), array_values($sums)),
            ];
        })->all();

        return ['labels' => array_values($buckets), 'datasets' => $datasets];
    }

    public function measuredShare(WasteFilters $filters): ?float
    {
        $collections = $filters->scope(WasteCollection::query())
            ->whereBetween('collected_at', [$filters->from()->startOfDay(), $filters->to()->endOfDay()])
            ->when($filters->kind() === WasteFilters::KIND_OPERATIONS, fn (Builder $query) => $query->whereNull('project_id'))
            ->when($filters->kind() === WasteFilters::KIND_CONSTRUCTION, fn (Builder $query) => $query->whereNotNull('project_id'));

        $total = (float) (clone $collections)->sum('mass_kg');

        return $total > 0 ? (float) (clone $collections)->where('mass_source', WasteMassSource::Measured->value)->sum('mass_kg') / $total * 100 : null;
    }

    public function missedCount(WasteFilters $filters): int
    {
        return $filters->scope(WastePickup::query())
            ->where('status', WastePickupStatus::Missed->value)
            ->whereBetween('due_on', [$filters->from()->toDateString(), $filters->to()->toDateString()])
            ->count();
    }

    public function costPerTonne(WasteFilters $filters): ?float
    {
        $tonnes = $filters->kind() === WasteFilters::KIND_CONSTRUCTION ? 0.0 : $this->totalMass($filters->withKind(WasteFilters::KIND_OPERATIONS)) / 1000;

        if ($tonnes <= 0) {
            return null;
        }

        $containerIds = $filters->scope(WasteContainer::query())->pluck('id');

        $costs = (float) CostEntry::query()
            ->where('costable_type', (new WasteContainer)->getMorphClass())
            ->whereIn('costable_id', $containerIds)
            ->whereDate('booked_on', '>=', $filters->from()->toDateString())
            ->whereDate('booked_on', '<=', $filters->to()->toDateString())
            ->sum('net_amount');

        return $costs > 0 ? $costs / $tonnes : null;
    }

    /**
     * @return array{nodes: array<int, array{id: string, label: string, column: int, kind: string, color?: string}>, links: array<int, array{source: string, target: string, value: float}>}
     */
    public function flow(WasteFilters $filters): array
    {
        $rows = $this->dailyValues($filters)
            ->selectRaw('waste_fraction_id, waste_treatment_category_id, SUM(mass_kg) as mass')
            ->groupBy('waste_fraction_id', 'waste_treatment_category_id')
            ->get();

        if ($rows->isEmpty()) {
            return ['nodes' => [], 'links' => []];
        }

        $fractions = WasteFraction::query()->whereIn('id', $rows->pluck('waste_fraction_id')->unique())->get()->keyBy('id');
        $categories = WasteTreatmentCategory::query()->whereIn('id', $rows->pluck('waste_treatment_category_id')->filter()->unique())->get()->keyBy('id');
        $nodes = [];
        $links = [];

        foreach ($rows as $row) {
            $fraction = $fractions[$row->waste_fraction_id] ?? null;
            $mass = (float) $row->getAttribute('mass');

            if ($fraction === null || $mass <= 0) {
                continue;
            }

            $category = $categories[$row->waste_treatment_category_id] ?? null;
            $categoryNode = 'category:'.($category?->getKey() ?? 'unknown');

            $nodes['fraction:'.$fraction->getKey()] ??= ['id' => 'fraction:'.$fraction->getKey(), 'label' => $fraction->name, 'column' => 0, 'kind' => 'fraction', 'color' => $fraction->displayColor()];
            $nodes[$categoryNode] ??= [
                'id' => $categoryNode,
                'label' => $category?->name ?? __('waste.report.unknown_destination'),
                'column' => 1,
                'kind' => $category === null ? 'unknown' : ($category->is_recovery ? 'recovery' : 'disposal'),
                'color' => $category?->color ?: '#94a3b8',
            ];
            $links[] = ['source' => 'fraction:'.$fraction->getKey(), 'target' => $categoryNode, 'value' => round($mass / 1000, 3)];
        }

        return ['nodes' => array_values($nodes), 'links' => $links];
    }

    /**
     * @return array{kg: float, coverage: float|null}
     */
    public function co2e(WasteFilters $filters): array
    {
        $rows = $this->dailyValues($filters)
            ->selectRaw('waste_fraction_id, waste_treatment_category_id, SUM(mass_kg) as mass')
            ->groupBy('waste_fraction_id', 'waste_treatment_category_id')
            ->get();

        $total = (float) $rows->sum(fn (WasteDailyValue $row): float => (float) $row->getAttribute('mass'));

        if ($total <= 0) {
            return ['kg' => 0.0, 'coverage' => null];
        }

        $year = $filters->to()->year;
        $factors = WasteEmissionFactor::query()
            ->where(fn (Builder $query) => $query->whereNull('valid_year')->orWhere('valid_year', '<=', $year))
            ->orderByRaw('valid_year IS NULL')
            ->orderByDesc('valid_year')
            ->get();

        $kg = 0.0;
        $covered = 0.0;

        foreach ($rows as $row) {
            $factor = $this->emissionFactor($factors, $row->waste_fraction_id, $row->waste_treatment_category_id);

            if ($factor === null) {
                continue;
            }

            $mass = (float) $row->getAttribute('mass');
            $kg += $mass / 1000 * (float) $factor->kg_co2e_per_t;
            $covered += $mass;
        }

        return ['kg' => round($kg, 1), 'coverage' => $covered / $total * 100];
    }

    /**
     * @param  Collection<int, WasteEmissionFactor>  $factors
     */
    private function emissionFactor(Collection $factors, ?string $fractionId, ?string $categoryId): ?WasteEmissionFactor
    {
        return $factors->first(fn (WasteEmissionFactor $factor): bool => $factor->waste_fraction_id === $fractionId && $factor->waste_treatment_category_id !== null && $factor->waste_treatment_category_id === $categoryId)
            ?? $factors->first(fn (WasteEmissionFactor $factor): bool => $factor->waste_fraction_id === $fractionId && $factor->waste_treatment_category_id === null)
            ?? $factors->first(fn (WasteEmissionFactor $factor): bool => $factor->waste_fraction_id === null && $factor->waste_treatment_category_id !== null && $factor->waste_treatment_category_id === $categoryId);
    }
}
