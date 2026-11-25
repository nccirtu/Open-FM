<?php

namespace App\Services\Energy;

use App\Enums\EnergyGoalPeriod;
use App\Enums\EnergyGoalType;
use App\Models\Asset;
use App\Models\EnergyGoal;
use App\Models\Meter;
use App\Models\Property;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

final class EnergyGoalEvaluator
{
    public function __construct(private EnergyReport $report) {}

    /**
     * @return array{actual: float, forecast: float, target: float|null, ratio: float|null, status: string, unit: string, from: CarbonImmutable, to: CarbonImmutable}
     */
    public function evaluate(EnergyGoal $goal, ?CarbonInterface $today = null): array
    {
        $today = CarbonImmutable::parse($today ?? now())->startOfDay();
        [$from, $to] = $this->period($goal, $today);

        $elapsedEnd = $today->lessThan($to) ? $today : $to;
        $elapsedDays = $today->lessThan($from) ? 0 : (int) round($from->diffInDays($elapsedEnd)) + 1;
        $totalDays = (int) round($from->diffInDays($to)) + 1;

        $actual = $elapsedDays > 0 ? $this->consumption($goal, $from, $elapsedEnd) : 0.0;
        $forecast = $elapsedDays > 0 ? $actual / $elapsedDays * $totalDays : 0.0;
        $target = $this->target($goal, $from, $to);
        $unit = $goal->medium?->evaluationUnit() ?? 'kWh';

        if ($goal->type === EnergyGoalType::MaxIntensity) {
            $area = $this->area($goal);
            $actual = $area ? $actual / $area : 0.0;
            $forecast = $area ? $forecast / $area : 0.0;
            $unit .= '/m²';
        }

        $status = match (true) {
            $target === null => 'unknown',
            $actual > $target => 'crit',
            $forecast > $target => 'warn',
            default => 'good',
        };

        return [
            'actual' => round($actual, 2),
            'forecast' => round($forecast, 2),
            'target' => $target !== null ? round($target, 2) : null,
            'ratio' => $target ? $forecast / $target : null,
            'status' => $status,
            'unit' => $unit,
            'from' => $from,
            'to' => $to,
        ];
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function period(EnergyGoal $goal, CarbonImmutable $today): array
    {
        $year = CarbonImmutable::create($goal->year)->startOfYear();

        if ($goal->period !== EnergyGoalPeriod::Month) {
            return [$year, $year->endOfYear()->startOfDay()];
        }

        $month = match (true) {
            $today->year === $goal->year => $today->startOfMonth(),
            $today->year > $goal->year => $year->endOfYear()->startOfMonth(),
            default => $year,
        };

        return [$month, $month->endOfMonth()->startOfDay()];
    }

    private function target(EnergyGoal $goal, CarbonImmutable $from, CarbonImmutable $to): ?float
    {
        if ($goal->type !== EnergyGoalType::Reduction) {
            return (float) $goal->target_value;
        }

        if ($goal->baseline_year === null) {
            return null;
        }

        $offset = $goal->year - $goal->baseline_year;
        $baseline = $this->consumption($goal, $from->subYears($offset), $to->subYears($offset));

        return $baseline > 0 ? $baseline * (1 - (float) $goal->target_value / 100) : null;
    }

    private function consumption(EnergyGoal $goal, CarbonInterface $from, CarbonInterface $to): float
    {
        $subject = $goal->goalable;

        return match (true) {
            $subject instanceof Meter => $this->report->consumption([$subject], $from, $to),
            $subject instanceof Asset => $this->report->assetConsumption($subject, $goal->medium, $from, $to),
            $subject instanceof Property => $this->report->consumption($this->report->topLevelMeters($goal->medium, $subject), $from, $to),
            default => $this->report->consumption($this->report->topLevelMeters($goal->medium), $from, $to),
        };
    }

    private function area(EnergyGoal $goal): ?float
    {
        $subject = $goal->goalable;

        if ($subject instanceof Property) {
            return $subject->referenceArea();
        }

        if ($subject === null) {
            $area = Property::query()->get()->sum(fn (Property $property): float => $property->referenceArea() ?? 0.0);

            return $area > 0 ? $area : null;
        }

        return null;
    }
}
