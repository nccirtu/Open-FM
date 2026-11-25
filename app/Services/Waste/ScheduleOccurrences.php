<?php

namespace App\Services\Waste;

use App\Enums\WasteIntervalUnit;
use App\Enums\WasteScheduleSource;
use App\Models\WasteSchedule;
use Carbon\CarbonImmutable;

final class ScheduleOccurrences
{
    /**
     * @return array<int, CarbonImmutable>
     */
    public function between(WasteSchedule $schedule, CarbonImmutable $from, CarbonImmutable $until): array
    {
        $startsOn = CarbonImmutable::parse($schedule->starts_on)->startOfDay();
        $from = $from->startOfDay()->max($startsOn);
        $until = $until->startOfDay();

        if ($schedule->ends_on !== null) {
            $until = $until->min(CarbonImmutable::parse($schedule->ends_on)->startOfDay());
        }

        if ($from->greaterThan($until)) {
            return [];
        }

        $dates = match ($schedule->source) {
            WasteScheduleSource::Rule => match ($schedule->interval_unit) {
                WasteIntervalUnit::Month => $this->monthly($schedule, $startsOn, $from, $until),
                WasteIntervalUnit::Year => $this->yearly($schedule, $startsOn, $from, $until),
                WasteIntervalUnit::Once => $startsOn->betweenIncluded($from, $until) ? [$startsOn] : [],
                default => $this->weekly($schedule, $startsOn, $from, $until),
            },
            WasteScheduleSource::Ics => $this->imported($schedule, $from, $until),
            WasteScheduleSource::OnDemand => [],
        };

        return collect($dates)
            ->unique(fn (CarbonImmutable $date): string => $date->toDateString())
            ->sortBy(fn (CarbonImmutable $date): string => $date->toDateString())
            ->values()
            ->all();
    }

    /**
     * @return array<int, CarbonImmutable>
     */
    private function weekly(WasteSchedule $schedule, CarbonImmutable $startsOn, CarbonImmutable $from, CarbonImmutable $until): array
    {
        $weekday = $schedule->weekday ?: $startsOn->dayOfWeekIso;
        $step = max(1, (int) $schedule->interval_value);

        $first = $startsOn;

        while ($first->dayOfWeekIso !== $weekday) {
            $first = $first->addDay();
        }

        if ($first->lessThan($from)) {
            $weeksBehind = (int) ceil($first->diffInDays($from) / 7 / $step) * $step;
            $first = $first->addWeeks($weeksBehind);
        }

        $dates = [];

        for ($date = $first; $date->lessThanOrEqualTo($until); $date = $date->addWeeks($step)) {
            if ($date->greaterThanOrEqualTo($from)) {
                $dates[] = $date;
            }
        }

        return $dates;
    }

    /**
     * @return array<int, CarbonImmutable>
     */
    private function monthly(WasteSchedule $schedule, CarbonImmutable $startsOn, CarbonImmutable $from, CarbonImmutable $until): array
    {
        $weekday = $schedule->weekday ?: $startsOn->dayOfWeekIso;
        $weekOfMonth = $schedule->week_of_month ?: 1;
        $step = max(1, (int) $schedule->interval_value);

        $dates = [];

        for ($month = $startsOn->startOfMonth(); $month->lessThanOrEqualTo($until); $month = $month->addMonthsNoOverflow($step)) {
            $date = $this->weekdayOfMonth($month, $weekday, $weekOfMonth);

            if ($date !== null && $date->greaterThanOrEqualTo($from) && $date->lessThanOrEqualTo($until)) {
                $dates[] = $date;
            }
        }

        return $dates;
    }

    /**
     * @return array<int, CarbonImmutable>
     */
    private function yearly(WasteSchedule $schedule, CarbonImmutable $startsOn, CarbonImmutable $from, CarbonImmutable $until): array
    {
        $step = max(1, (int) $schedule->interval_value);
        $dates = [];

        for ($years = 0, $date = $startsOn; $date->lessThanOrEqualTo($until); $years += $step, $date = $startsOn->addYearsNoOverflow($years)) {
            if ($date->greaterThanOrEqualTo($from)) {
                $dates[] = $date;
            }
        }

        return $dates;
    }

    private function weekdayOfMonth(CarbonImmutable $month, int $weekday, int $weekOfMonth): ?CarbonImmutable
    {
        if ($weekOfMonth === -1) {
            $date = $month->endOfMonth()->startOfDay();

            while ($date->dayOfWeekIso !== $weekday) {
                $date = $date->subDay();
            }

            return $date;
        }

        $date = $month->startOfMonth();

        while ($date->dayOfWeekIso !== $weekday) {
            $date = $date->addDay();
        }

        $date = $date->addWeeks($weekOfMonth - 1);

        return $date->month === $month->month ? $date : null;
    }

    /**
     * @return array<int, CarbonImmutable>
     */
    private function imported(WasteSchedule $schedule, CarbonImmutable $from, CarbonImmutable $until): array
    {
        return collect($schedule->ics_dates ?? [])
            ->map(fn (string $date): CarbonImmutable => CarbonImmutable::parse($date)->startOfDay())
            ->filter(fn (CarbonImmutable $date): bool => $date->betweenIncluded($from, $until))
            ->values()
            ->all();
    }
}
