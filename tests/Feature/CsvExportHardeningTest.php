<?php

use App\Filament\Exports\AssetExporter;
use App\Filament\Exports\WasteRegisterExporter;
use App\Models\Asset;
use App\Models\WasteCollection;
use App\Support\Csv;
use Filament\Actions\Exports\Models\Export;

it('neutralizes formulas in the asset export', function () {
    loginAsAdminInFmPanel();

    $asset = Asset::factory()->create([
        'name' => '=HYPERLINK("http://x","y")',
        'manufacturer' => '@SUM(1+1)',
        'serial_number' => '-12.5',
    ]);

    $exporter = new AssetExporter(new Export, ['name' => 'name', 'manufacturer' => 'manufacturer', 'serial_number' => 'serial_number'], []);

    expect($exporter($asset))->toBe(["'=HYPERLINK(\"http://x\",\"y\")", "'@SUM(1+1)", '-12.5']);
});

it('neutralizes formulas in the hazardous waste register export', function () {
    loginAsAdminInWastePanel();

    $collection = WasteCollection::factory()->create([
        'document_number' => '=cmd|\' /C calc\'!A0',
        'note' => '+49 30 123456',
    ]);

    $exporter = new WasteRegisterExporter(new Export, ['document_number' => 'document_number', 'note' => 'note'], []);

    expect($exporter($collection->load(['property', 'fraction', 'company', 'treatment'])))->toBe(["'=cmd|' /C calc'!A0", "'+49 30 123456"]);
});

it('neutralizes formulas in downloaded csv files', function () {
    $response = Csv::download('test.csv', ['Name'], [['=1+1']]);

    ob_start();
    $response->sendContent();
    $content = ob_get_clean();

    expect($content)->toContain("'=1+1");
});
