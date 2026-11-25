<?php

namespace App\Services\Waste;

use App\Enums\WasteGoalType;
use App\Models\Property;
use App\Models\WasteGoal;
use App\Support\WasteFilters;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

final class WasteGoalEvaluator
{
    private const RATE_TOLERANCE = 5.0;

    public function __construct(private WasteReport $report) {}

    /**
     * @return array{actual: float|null, forecast: float|null, target: float|null, status: string, unit: string}
     */
    public function evaluate(WasteGoal $goal, ?CarbonInterface $today = null): array
    {
        $today = CarbonImmutable::parse($today ?? now())->startOfDay();
        $from = CarbonImmutable::create($goal->year)->startOfYear();
        $to = $from->endOfYear()->startOfDay();

        if ($today->lessThan($from)) {
            return $this->result(null, null, $this->plannedTarget($goal), 'unknown', $goal->type->unit());
        }

        $elapsedEnd = $today->lessThan($to) ? $today : $to;
        $filters = $this->filters($goal, $from, $elapsedEnd, $today);
        $share = ((int) round($from->diffInDays($to)) + 1) / ((int) round($from->diffInDays($elapsedEnd)) + 1);

        return match ($goal->type) {
            WasteGoalType::MinRecyclingRate => $this->rate($this->report->recyclingRate($filters), (float) $goal->target_value),
            WasteGoalType::MinSeparationRate => $this->rate($this->report->separationRate($filters), (float) $goal->target_value),
            WasteGoalType::MaxMass => $this->mass($this->report->totalMass($filters) / 1000, $share, (float) $goal->target_value, 't'),
            WasteGoalType::Reduction => $this->mass($this->report->totalMass($filters) / 1000, $share, $this->reductionTarget($goal, $today), 't'),
            WasteGoalType::MaxMassPerArea => $this->perArea($goal, $filters, $share),
        };
    }

    public function plannedTarget(WasteGoal $goal): ?float
    {
        return $goal->type === WasteGoalType::Reduction ? $this->reductionTarget($goal, CarbonImmutable::today()) : (float) $goal->target_value;
    }

    private function rate(?float $actual, float $target): array
    {
        $status = match (true) {
            $actual === null => 'unknown',
            $actual >= $target => 'good',
            $actual >= $target - self::RATE_TOLERANCE => 'warn',
            default => 'crit',
        };

        return $this->result($actual, null, $target, $status, '%');
    }

    private function mass(float $actual, float $share, ?float $target, string $unit): array
    {
        $forecast = $actual * $share;

        $status = match (true) {
            $target === null => 'unknown',
            $actual > $target => 'crit',
            $forecast > $target => 'warn',
            default => 'good',
        };

        return $this->result($actual, $forecast, $target, $status, $unit);
    }

    private function perArea(WasteGoal $goal, WasteFilters $filters, float $share): array
    {
        $area = $goal->property_id !== null
            ? Property::query()->find($goal->property_id)?->referenceArea()
            : $filters->properties()->get()->sum(fn (Property $property): float => $property->referenceArea() ?? 0.0);

        if (! $area) {
            return $this->result(null, null, (float) $goal->target_value, 'unknown', 'kg/m²');
        }

        return $this->mass($this->report->totalMass($filters) / $area, $share, (float) $goal->target_value, 'kg/m²');
    }

    private function reductionTarget(WasteGoal $goal, CarbonImmutable $today): ?float
    {
        if ($goal->baseline_year === null) {
            return null;
        }

        $from = CarbonImmutable::create($goal->baseline_year)->startOfYear();
        $baseline = $this->report->totalMass($this->filters($goal, $from, $from->endOfYear()->startOfDay(), $today)) / 1000;

        return $baseline > 0 ? $baseline * (1 - (float) $goal->target_value / 100) : null;
    }

    private function filters(WasteGoal $goal, CarbonImmutable $from, CarbonImmutable $until, CarbonImmutable $today): WasteFilters
    {
        return WasteFilters::fromArray([
            'period' => 'custom',
            'from' => $from->toDateString(),
            'until' => $until->toDateString(),
            'property_id' => $goal->property_id,
        ], $today->greaterThan($until) ? $today : $until);
    }

    /**
     * @return array{actual: float|null, forecast: float|null, target: float|null, status: string, unit: string}
     */
    private function result(?float $actual, ?float $forecast, ?float $target, string $status, string $unit): array
    {
        return [
            'actual' => $actual !== null ? round($actual, 2) : null,
            'forecast' => $forecast !== null ? round($forecast, 2) : null,
            'target' => $target !== null ? round($target, 2) : null,
            'status' => $status,
            'unit' => $unit,
        ];
    }
}
