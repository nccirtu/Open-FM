<?php

namespace App\Services\Energy;

use App\Models\Meter;
use App\Models\MeterDailyValue;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class ConsumptionAggregator
{
    public function refresh(Meter $meter, ?CarbonInterface $from = null, ?CarbonInterface $to = null): void
    {
        if ($meter->isVirtual()) {
            $this->refreshVirtual($meter, $from, $to);

            return;
        }

        $this->refreshPhysical($meter, $from, $to);

        foreach ($this->dependentVirtualMeters($meter) as $virtual) {
            $this->refreshVirtual($virtual, $from, $to);
        }
    }

    private function refreshPhysical(Meter $meter, ?CarbonInterface $from, ?CarbonInterface $to): void
    {
        $fromDay = $from !== null ? CarbonImmutable::parse($from)->startOfDay() : null;
        $toDay = $to !== null ? CarbonImmutable::parse($to)->endOfDay() : null;

        $query = $meter->readings()->orderBy('read_at');

        if ($fromDay !== null) {
            $before = $meter->readings()->where('read_at', '<', $fromDay)->orderByDesc('read_at')->value('read_at');
            $query->where('read_at', '>=', $before ?? $fromDay);
        }

        if ($toDay !== null) {
            $after = $meter->readings()->where('read_at', '>', $toDay)->orderBy('read_at')->value('read_at');
            $query->where('read_at', '<=', $after ?? $toDay);
        }

        $readings = $query->get(['read_at', 'value']);
        $factor = (float) $meter->factor ?: 1.0;
        $days = [];

        foreach ($readings->values() as $index => $reading) {
            $next = $readings[$index + 1] ?? null;

            if ($next === null) {
                break;
            }

            $start = CarbonImmutable::parse($reading->read_at);
            $end = CarbonImmutable::parse($next->read_at);
            $seconds = $end->getTimestamp() - $start->getTimestamp();

            if ($seconds <= 0) {
                continue;
            }

            $delta = max(0.0, (float) $next->value - (float) $reading->value) * $factor;

            for ($day = $start->startOfDay(); $day->lessThan($end); $day = $day->addDay()) {
                $overlap = min($end->getTimestamp(), $day->addDay()->getTimestamp()) - max($start->getTimestamp(), $day->getTimestamp());

                if ($overlap <= 0) {
                    continue;
                }

                $key = $day->toDateString();
                $days[$key] = ($days[$key] ?? 0.0) + $delta * $overlap / $seconds;
            }
        }

        $this->store($meter, $days, $fromDay, $toDay);
    }

    private function refreshVirtual(Meter $meter, ?CarbonInterface $from, ?CarbonInterface $to): void
    {
        $fromDay = $from !== null ? CarbonImmutable::parse($from)->startOfDay() : null;
        $toDay = $to !== null ? CarbonImmutable::parse($to)->endOfDay() : null;
        $signs = [];

        foreach ($meter->formula ?? [] as $term) {
            if (is_array($term) && filled($term['meter_id'] ?? null)) {
                $signs[(string) $term['meter_id']] = ((int) ($term['sign'] ?? 1)) < 0 ? -1 : 1;
            }
        }

        $days = [];

        if ($signs !== []) {
            MeterDailyValue::query()
                ->whereIn('meter_id', array_keys($signs))
                ->when($fromDay, fn ($query) => $query->whereDate('date', '>=', $fromDay->toDateString()))
                ->when($toDay, fn ($query) => $query->whereDate('date', '<=', $toDay->toDateString()))
                ->get(['meter_id', 'date', 'consumption'])
                ->each(function (MeterDailyValue $value) use (&$days, $signs): void {
                    $key = $value->date->toDateString();
                    $days[$key] = ($days[$key] ?? 0.0) + $signs[$value->meter_id] * (float) $value->consumption;
                });
        }

        $this->store($meter, $days, $fromDay, $toDay);
    }

    /**
     * @param  array<string, float>  $days
     */
    private function store(Meter $meter, array $days, ?CarbonImmutable $fromDay, ?CarbonImmutable $toDay): void
    {
        $meter->dailyValues()
            ->when($fromDay, fn ($query) => $query->whereDate('date', '>=', $fromDay->toDateString()))
            ->when($toDay, fn ($query) => $query->whereDate('date', '<=', $toDay->toDateString()))
            ->delete();

        $now = now();

        $rows = collect($days)
            ->filter(fn (float $value, string $date): bool => ($fromDay === null || $date >= $fromDay->toDateString())
                && ($toDay === null || $date <= $toDay->toDateString()))
            ->map(fn (float $value, string $date): array => [
                'id' => (string) Str::uuid7(),
                'organization_id' => $meter->organization_id,
                'meter_id' => $meter->getKey(),
                'date' => $date,
                'consumption' => round($value, 4),
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values();

        $rows->chunk(500)->each(fn (Collection $chunk) => MeterDailyValue::query()->insert($chunk->all()));
    }

    /**
     * @return Collection<int, Meter>
     */
    private function dependentVirtualMeters(Meter $meter): Collection
    {
        return Meter::query()
            ->withoutGlobalScopes()
            ->where('property_id', $meter->property_id)
            ->where('kind', 'virtual')
            ->get()
            ->filter(fn (Meter $virtual): bool => in_array($meter->getKey(), $virtual->formulaMeterIds(), true))
            ->values();
    }
}
