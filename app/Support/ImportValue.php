<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

final class ImportValue
{
    private const DATE_FORMATS = ['j.n.y', 'j.n.Y', 'Y-m-d', 'j/n/Y', 'j.n.Y H:i', 'j.n.Y H:i:s', 'Y-m-d H:i', 'Y-m-d H:i:s', 'Y-m-d\TH:i:s'];

    private const TRUE_VALUES = ['1', 'true', 'ja', 'j', 'yes', 'y', 'x', 'wahr', 'oui', 'si', 'sì', 'da', 'tak'];

    private const FALSE_VALUES = ['0', 'false', 'nein', 'n', 'no', 'falsch', 'non', 'ne', 'nie', 'nu'];

    public static function date(string $raw): Carbon
    {
        $raw = trim($raw);

        foreach (self::DATE_FORMATS as $format) {
            try {
                $date = Carbon::createFromFormat('!'.$format, $raw);
            } catch (Throwable) {
                continue;
            }

            $errors = Carbon::getLastErrors();

            if ($date !== null && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                return $date;
            }
        }

        throw new InvalidArgumentException(__('assets.import.errors.invalid_date', ['value' => $raw]));
    }

    public static function number(string $raw): int|float
    {
        $value = preg_replace('/[^\d,.\-]/', '', $raw) ?? '';

        if (str_contains($value, ',') && str_contains($value, '.')) {
            $value = strrpos($value, ',') > strrpos($value, '.')
                ? str_replace(['.', ','], ['', '.'], $value)
                : str_replace(',', '', $value);
        } elseif (substr_count($value, '.') > 1 || preg_match('/^-?\d{1,3}\.\d{3}$/', $value) === 1) {
            $value = str_replace('.', '', $value);
        } elseif (substr_count($value, ',') > 1) {
            $value = str_replace(',', '', $value);
        } else {
            $value = str_replace(',', '.', $value);
        }

        if (! is_numeric($value)) {
            throw new InvalidArgumentException(__('assets.import.errors.invalid_number', ['value' => $raw]));
        }

        return str_contains($value, '.') ? (float) $value : (int) $value;
    }

    public static function boolean(string $raw): bool
    {
        $value = Str::lower(trim($raw));

        return match (true) {
            in_array($value, self::TRUE_VALUES, true) => true,
            in_array($value, self::FALSE_VALUES, true) => false,
            default => throw new InvalidArgumentException(__('assets.import.errors.invalid_boolean', ['value' => $raw])),
        };
    }

    public static function normalizeHeader(string $header): string
    {
        return Str::of(str_replace("\u{FEFF}", '', $header))
            ->replace(['›', '»', '>', '|', ' - ', ' – '], ':')
            ->lower()
            ->replaceMatches('/\s*:\s*/u', ': ')
            ->squish()
            ->trim(': ')
            ->toString();
    }
}
