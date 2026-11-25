<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

final class Csv
{
    private const FORMULA_TRIGGERS = ['=', '+', '-', '@', "\t", "\r"];

    /**
     * @param  array<int, string>  $headers
     * @param  iterable<int, array<int, mixed>>  $rows
     */
    public static function make(array $headers, iterable $rows): string
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\u{FEFF}");
        fputcsv($handle, $headers, ';', '"', '\\');

        foreach ($rows as $row) {
            fputcsv($handle, array_map(self::cell(...), $row), ';', '"', '\\');
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    public static function cell(mixed $value): string
    {
        if (is_float($value)) {
            return number_format($value, 2, ',', '');
        }

        if (is_int($value)) {
            return (string) $value;
        }

        return self::neutralize((string) $value);
    }

    public static function neutralize(string $text): string
    {
        if ($text === '' || ! in_array($text[0], self::FORMULA_TRIGGERS, true)) {
            return $text;
        }

        if (is_numeric($text) && in_array($text[0], ['+', '-'], true)) {
            return $text;
        }

        if (preg_match('/^[+-]\d+,\d+$/', $text) === 1) {
            return $text;
        }

        return "'".$text;
    }

    /**
     * @param  array<int, string>  $headers
     * @param  iterable<int, array<int, mixed>>  $rows
     */
    public static function download(string $fileName, array $headers, iterable $rows): StreamedResponse
    {
        $csv = self::make($headers, $rows);

        return response()->streamDownload(function () use ($csv): void {
            echo $csv;
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
