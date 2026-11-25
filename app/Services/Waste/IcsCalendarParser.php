<?php

namespace App\Services\Waste;

use Carbon\CarbonImmutable;
use Throwable;

final class IcsCalendarParser
{
    /**
     * @return array<int, string>
     */
    public function dates(string $ics, ?string $filter = null): array
    {
        $filter = filled($filter) ? mb_strtolower(trim($filter)) : null;
        $dates = [];
        $event = null;

        foreach ($this->unfoldedLines($ics) as $line) {
            if (strcasecmp($line, 'BEGIN:VEVENT') === 0) {
                $event = ['start' => null, 'summary' => ''];

                continue;
            }

            if ($event === null) {
                continue;
            }

            if (strcasecmp($line, 'END:VEVENT') === 0) {
                if ($event['start'] !== null && ($filter === null || str_contains(mb_strtolower($event['summary']), $filter))) {
                    $dates[] = $event['start'];
                }

                $event = null;

                continue;
            }

            [$name, $value] = $this->property($line);

            if ($name === 'DTSTART') {
                $event['start'] = $this->date($value);
            } elseif ($name === 'SUMMARY') {
                $event['summary'] = str_replace(['\\,', '\;', '\\n'], [',', ';', ' '], $value);
            }
        }

        $dates = array_values(array_unique(array_filter($dates)));
        sort($dates);

        return $dates;
    }

    /**
     * @return array<int, string>
     */
    private function unfoldedLines(string $ics): array
    {
        $ics = str_replace(["\r\n", "\r"], "\n", $ics);
        $ics = preg_replace("/\n[ \t]/", '', $ics) ?? $ics;

        return array_values(array_filter(array_map('trim', explode("\n", $ics)), fn (string $line): bool => $line !== ''));
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function property(string $line): array
    {
        $colon = strpos($line, ':');

        if ($colon === false) {
            return ['', ''];
        }

        $name = strtoupper(strtok(substr($line, 0, $colon), ';') ?: '');

        return [$name, substr($line, $colon + 1)];
    }

    private function date(string $value): ?string
    {
        $value = trim($value);

        if (preg_match('/^(\d{8})/', $value, $match) !== 1) {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('Ymd', $match[1])?->toDateString();
        } catch (Throwable) {
            return null;
        }
    }
}
