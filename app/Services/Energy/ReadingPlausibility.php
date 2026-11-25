<?php

namespace App\Services\Energy;

use App\Enums\MeterReadingStatus;
use App\Models\Meter;
use App\Models\MeterDailyValue;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

final class ReadingPlausibility
{
    public const HIGH_FACTOR = 3.0;

    public const MIN_HISTORY_DAYS = 14;

    public function check(Meter $meter, CarbonInterface $readAt, float $value): PlausibilityResult
    {
        $previous = $meter->readings()->where('read_at', '<', $readAt)->orderByDesc('read_at')->first();

        if ($previous === null) {
            return new PlausibilityResult(MeterReadingStatus::Plausible);
        }

        if ($value < (float) $previous->value) {
            return new PlausibilityResult(MeterReadingStatus::Suspicious, 'decreased');
        }

        $days = max(($readAt->getTimestamp() - $previous->read_at->getTimestamp()) / 86400, 1 / 96);
        $actualDaily = ($value - (float) $previous->value) * ((float) $meter->factor ?: 1.0) / $days;
        $expectedDaily = $this->typicalDaily($meter, CarbonImmutable::parse($previous->read_at));

        if ($expectedDaily !== null && $expectedDaily > 0 && $actualDaily > $expectedDaily * self::HIGH_FACTOR) {
            return new PlausibilityResult(MeterReadingStatus::Suspicious, 'unusually_high', $actualDaily, $expectedDaily);
        }

        return new PlausibilityResult(MeterReadingStatus::Plausible, null, $actualDaily, $expectedDaily);
    }

    public function typicalDaily(Meter $meter, CarbonImmutable $until): ?float
    {
        $values = MeterDailyValue::query()
            ->where('meter_id', $meter->getKey())
            ->whereDate('date', '<', $until->toDateString())
            ->whereDate('date', '>=', $until->subYear()->toDateString())
            ->pluck('consumption');

        if ($values->count() < self::MIN_HISTORY_DAYS) {
            return null;
        }

        return (float) $values->avg();
    }
}
