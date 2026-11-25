<?php

namespace App\Services\Energy;

use App\Enums\EnergyMedium;
use App\Models\Asset;
use App\Models\Meter;
use App\Models\MeterDailyValue;
use App\Models\Property;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class EnergyReport
{
    /**
     * @param  iterable<int, Meter|string>|Builder<Meter>  $meters
     */
    public function consumption(iterable|Builder $meters, CarbonInterface $from, CarbonInterface $to): float
    {
        return (float) MeterDailyValue::query()
            ->whereIn('meter_id', $this->meterIds($meters))
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString())
            ->sum('consumption');
    }

    /**
     * @param  iterable<int, Meter|string>|Builder<Meter>  $meters
     * @return array<string, float>
     */
    public function consumptionByMeter(iterable|Builder $meters, CarbonInterface $from, CarbonInterface $to): array
    {
        return MeterDailyValue::query()
            ->whereIn('meter_id', $this->meterIds($meters))
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString())
            ->get(['meter_id', 'consumption'])
            ->groupBy('meter_id')
            ->map(fn (Collection $values): float => (float) $values->sum('consumption'))
            ->all();
    }

    /**
     * @param  iterable<int, Meter|string>|Builder<Meter>  $meters
     * @return array<string, float>
     */
    public function monthlyTotals(iterable|Builder $meters, CarbonInterface $firstMonth, int $months): array
    {
        $start = CarbonImmutable::parse($firstMonth)->startOfMonth();
        $end = $start->addMonths($months)->subDay();
        $totals = [];

        for ($month = $start; $month->lessThanOrEqualTo($end); $month = $month->addMonth()) {
            $totals[$month->format('Y-m')] = 0.0;
        }

        MeterDailyValue::query()
            ->whereIn('meter_id', $this->meterIds($meters))
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $end->toDateString())
            ->get(['date', 'consumption'])
            ->each(function (MeterDailyValue $value) use (&$totals): void {
                $totals[$value->date->format('Y-m')] += (float) $value->consumption;
            });

        return $totals;
    }

    /**
     * @param  iterable<int, Meter|string>|Builder<Meter>  $meters
     * @return array<string, float>
     */
    public function dailyTotals(iterable|Builder $meters, CarbonInterface $from, CarbonInterface $to): array
    {
        $totals = [];

        for ($day = CarbonImmutable::parse($from)->startOfDay(); $day->lessThanOrEqualTo($to); $day = $day->addDay()) {
            $totals[$day->toDateString()] = 0.0;
        }

        MeterDailyValue::query()
            ->whereIn('meter_id', $this->meterIds($meters))
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString())
            ->get(['date', 'consumption'])
            ->each(function (MeterDailyValue $value) use (&$totals): void {
                $totals[$value->date->toDateString()] = ($totals[$value->date->toDateString()] ?? 0.0) + (float) $value->consumption;
            });

        return $totals;
    }

    /**
     * @return array<string, float|null>
     */
    public function dailySeries(Meter $meter, CarbonInterface $from, CarbonInterface $to): array
    {
        $values = $meter->dailyValues()
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString())
            ->get(['date', 'consumption'])
            ->mapWithKeys(fn (MeterDailyValue $value): array => [$value->date->toDateString() => (float) $value->consumption]);

        $series = [];

        for ($day = CarbonImmutable::parse($from)->startOfDay(); $day->lessThanOrEqualTo($to); $day = $day->addDay()) {
            $series[$day->toDateString()] = $values[$day->toDateString()] ?? null;
        }

        return $series;
    }

    /**
     * @param  Collection<int, Meter>  $meters
     * @return Collection<int, array{asset: Asset, consumption: float}>
     */
    public function perAsset(Collection $meters, CarbonInterface $from, CarbonInterface $to): Collection
    {
        $byMeter = $this->consumptionByMeter($meters, $from, $to);
        $assets = [];

        foreach ($meters as $meter) {
            foreach ($meter->measuredAssets as $asset) {
                $share = (float) ($asset->pivot->share ?? 100) / 100;
                $assets[$asset->getKey()] ??= ['asset' => $asset, 'consumption' => 0.0];
                $assets[$asset->getKey()]['consumption'] += ($byMeter[$meter->getKey()] ?? 0.0) * $share;
            }
        }

        return collect($assets)->sortByDesc('consumption')->values();
    }

    public function assetConsumption(Asset $asset, ?EnergyMedium $medium, CarbonInterface $from, CarbonInterface $to): float
    {
        $meters = $asset->measuringMeters()
            ->when($medium, fn ($query) => $query->where('medium', $medium->value))
            ->get();

        $byMeter = $this->consumptionByMeter($meters, $from, $to);

        return (float) $meters->sum(fn (Meter $meter): float => ($byMeter[$meter->getKey()] ?? 0.0) * (float) ($meter->pivot->share ?? 100) / 100);
    }

    /**
     * @return Builder<Meter>
     */
    public function topLevelMeters(EnergyMedium $medium, ?Property $property = null): Builder
    {
        return Meter::query()
            ->topLevel()
            ->where('medium', $medium->value)
            ->when($property, fn (Builder $query) => $query->where('property_id', $property->getKey()));
    }

    public function intensity(Property $property, EnergyMedium $medium, int $year): ?float
    {
        $area = $property->referenceArea();

        if ($area === null) {
            return null;
        }

        $from = CarbonImmutable::create($year)->startOfYear();
        $to = $from->endOfYear();

        return $this->consumption($this->topLevelMeters($medium, $property), $from, $to) / $area;
    }

    /**
     * @param  iterable<int, Meter|string>|Builder<Meter>  $meters
     * @return array<int, string>
     */
    private function meterIds(iterable|Builder $meters): array
    {
        if ($meters instanceof Builder) {
            return $meters->pluck('meters.id')->all();
        }

        return collect($meters)->map(fn (Meter|string $meter): string => $meter instanceof Meter ? $meter->getKey() : $meter)->values()->all();
    }
}
