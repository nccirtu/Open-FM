<?php

namespace App\Services\Energy;

use App\Enums\MeterReadingStatus;

final readonly class PlausibilityResult
{
    public function __construct(
        public MeterReadingStatus $status,
        public ?string $flag = null,
        public ?float $actualDaily = null,
        public ?float $expectedDaily = null,
    ) {}

    public function isSuspicious(): bool
    {
        return $this->status === MeterReadingStatus::Suspicious;
    }

    public function message(string $unit): ?string
    {
        return match ($this->flag) {
            'decreased' => __('energy.plausibility.decreased'),
            'unusually_high' => __('energy.plausibility.unusually_high', [
                'actual' => number_format((float) $this->actualDaily, 1, ',', '.'),
                'expected' => number_format((float) $this->expectedDaily, 1, ',', '.'),
                'unit' => $unit,
            ]),
            default => null,
        };
    }
}
