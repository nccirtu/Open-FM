<?php

use App\Support\Csv;

it('prefixes text cells that spreadsheets would read as formulas', function (string $value) {
    expect(Csv::cell($value))->toBe("'".$value);
})->with([
    'equals' => '=HYPERLINK("http://x","y")',
    'plus' => '+cmd|\' /C calc\'!A0',
    'minus' => '-2+3+cmd|\' /C calc\'!A0',
    'at' => '@SUM(1+1)',
    'tab' => "\t=1+1",
    'carriage return' => "\r=1+1",
    'phone number with spaces' => '+49 30 123456',
    'minus sign only' => '-',
]);

it('leaves numbers and harmless text unchanged', function (mixed $value, string $expected) {
    expect(Csv::cell($value))->toBe($expected);
})->with([
    'negative numeric string' => ['-12.5', '-12.5'],
    'signed numeric string' => ['+42', '+42'],
    'german decimal string' => ['-12,50', '-12,50'],
    'integer' => [-5, '-5'],
    'negative float' => [-12.5, '-12,50'],
    'float' => [3.14159, '3,14'],
    'null' => [null, ''],
    'empty string' => ['', ''],
    'plain text' => ['Pumpe 1', 'Pumpe 1'],
    'text with inner equals' => ['a=b', 'a=b'],
    'text with leading space' => [' =1+1', ' =1+1'],
]);

it('neutralizes formulas in the generated csv and keeps numbers numeric', function () {
    $csv = Csv::make(['Name', 'Betrag'], [
        ['=HYPERLINK("http://x","y")', -12.5],
        ['@evil', '-12.5'],
        ['Normal', 7],
    ]);

    expect($csv)->toStartWith("\u{FEFF}Name;Betrag\n")
        ->and($csv)->toContain("\"'=HYPERLINK(\"\"http://x\"\",\"\"y\"\")\";-12,50\n")
        ->and($csv)->toContain("'@evil;-12.5\n")
        ->and($csv)->toContain("Normal;7\n");
});
