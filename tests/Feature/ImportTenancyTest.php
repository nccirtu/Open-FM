<?php

use App\Filament\Imports\WasteCollectionImporter;
use App\Filament\Imports\WasteContainerImporter;
use App\Models\Organization;
use App\Models\Property;
use App\Models\WasteContainer;
use App\Models\WasteFraction;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\Models\Import;

it('does not resolve waste containers of other organizations in queued imports', function () {
    loginAsAdminInWastePanel();

    $other = Organization::factory()->create();
    inOrganization($other, fn (): WasteContainer => WasteContainer::factory()->create(['number' => 'FREMD-1']));

    $importer = new WasteCollectionImporter(
        new Import,
        ['container' => 'Behälter', 'collected_at' => 'Datum', 'mass_kg' => 'Gewicht'],
        ['organization_id' => defaultOrganization()->getKey()],
    );

    withoutTenantScopes(fn () => expect(fn () => $importer(['Behälter' => 'FREMD-1', 'Datum' => '07.10.2026', 'Gewicht' => '1']))
        ->toThrow(RowImportFailedException::class));
});

it('does not attach imported containers to properties or fractions of other organizations', function () {
    loginAsAdminInWastePanel();

    $other = Organization::factory()->create();
    inOrganization($other, function (): void {
        Property::factory()->create(['property_number' => 'FREMD-OBJ']);
        WasteFraction::factory()->paper()->create(['name' => 'Fremdes Papier']);
    });

    $importer = new WasteContainerImporter(
        new Import,
        ['number' => 'Nr', 'property' => 'Objekt', 'fraction' => 'Fraktion', 'volume_l' => 'Liter'],
        ['organization_id' => defaultOrganization()->getKey()],
    );

    withoutTenantScopes(fn () => expect(fn () => $importer(['Nr' => 'B-1', 'Objekt' => 'FREMD-OBJ', 'Fraktion' => 'Fremdes Papier', 'Liter' => '240']))
        ->toThrow(RowImportFailedException::class));

    expect(WasteContainer::query()->withoutGlobalScopes()->where('number', 'B-1')->exists())->toBeFalse();
});
