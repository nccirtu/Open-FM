<?php

namespace App\Support;

final class MemoryLimit
{
    public static function raiseTo(string $limit): void
    {
        $current = self::bytes((string) ini_get('memory_limit'));
        $wanted = self::bytes($limit);

        if ($current === -1) {
            return;
        }

        if ($wanted === -1 || $wanted > $current) {
            ini_set('memory_limit', $limit);
        }
    }

    public static function bytes(string $value): int
    {
        $value = trim($value);

        if ($value === '' || $value === '-1') {
            return -1;
        }

        $number = (int) $value;

        return match (strtoupper(substr($value, -1))) {
            'G' => $number * 1024 ** 3,
            'M' => $number * 1024 ** 2,
            'K' => $number * 1024,
            default => $number,
        };
    }
}
