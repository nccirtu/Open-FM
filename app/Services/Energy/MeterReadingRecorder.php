<?php

namespace App\Services\Energy;

use App\Enums\MeterReadingSource;
use App\Models\Meter;
use App\Models\MeterReading;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

final class MeterReadingRecorder
{
    public function __construct(
        private ConsumptionAggregator $aggregator,
        private ReadingPlausibility $plausibility,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function record(Meter $meter, CarbonInterface $readAt, float $value, MeterReadingSource $source, array $attributes = []): MeterReading
    {
        if ($meter->isVirtual()) {
            throw new InvalidArgumentException(__('energy.errors.virtual_meter'));
        }

        $readAt = CarbonImmutable::parse($readAt)->setMicrosecond(0);
        $check = $this->plausibility->check($meter, $readAt, $value);

        $reading = MeterReading::query()
            ->where('meter_id', $meter->getKey())
            ->where('read_at', $readAt)
            ->first() ?? new MeterReading;

        $reading->fill([
            ...$attributes,
            'meter_id' => $meter->getKey(),
            'read_at' => $readAt,
            'value' => $value,
            'source' => $source,
            'status' => $check->status,
            'flag' => $check->flag,
        ]);
        $reading->organization_id = $meter->organization_id;
        $reading->created_by ??= auth()->id();
        $reading->save();

        $this->refreshAround($meter, $readAt);

        return $reading;
    }

    public function delete(MeterReading $reading): void
    {
        $meter = $reading->meter;
        $readAt = CarbonImmutable::parse($reading->read_at);

        $reading->delete();

        if ($meter !== null) {
            $this->refreshAround($meter, $readAt);
        }
    }

    public function check(Meter $meter, CarbonInterface $readAt, float $value): PlausibilityResult
    {
        return $this->plausibility->check($meter, $readAt, $value);
    }

    private function refreshAround(Meter $meter, CarbonImmutable $readAt): void
    {
        $previous = $meter->readings()->where('read_at', '<', $readAt)->orderByDesc('read_at')->value('read_at');
        $next = $meter->readings()->where('read_at', '>', $readAt)->orderBy('read_at')->value('read_at');

        $this->aggregator->refresh(
            $meter,
            $previous !== null ? CarbonImmutable::parse($previous) : $readAt,
            $next !== null ? CarbonImmutable::parse($next) : $readAt,
        );
    }
}
