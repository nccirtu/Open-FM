<?php

use App\Filament\Resources\PropertyResource\Pages\BimPage;
use App\Filament\Waste\Pages\CostAllocationPage;
use App\Models\Asset;
use App\Models\Property;
use App\Models\User;
use Livewire\Livewire;

it('refuses to resolve ifc guids against classes other than assets and structure nodes', function (string $model) {
    loginAsAdminInFmPanel();

    $property = Property::factory()->create();

    Livewire::test(BimPage::class, ['record' => $property->getKey()])
        ->call('findByGuid', $model, 'GUID-1')
        ->assertForbidden();
})->with([User::class, Property::class, 'Illuminate\\Support\\Facades\\DB', 'nonexistent']);

it('still resolves ifc guids of assets through the public method', function () {
    loginAsAdminInFmPanel();

    $property = Property::factory()->create();
    $asset = Asset::factory()->create(['property_id' => $property->getKey(), 'name' => 'RLT-Gerät']);
    $asset->forceFill(['ifc_guid' => 'GUID-RLT'])->saveQuietly();

    $component = Livewire::test(BimPage::class, ['record' => $property->getKey()])->instance();

    expect($component->findByGuid(Asset::class, 'GUID-RLT')?->getKey())->toBe($asset->getKey());
});

it('only applies the known filters on the cost allocation page', function (string $name) {
    loginAsAdminInWastePanel();

    Livewire::test(CostAllocationPage::class)
        ->call('applyFilter', $name, 'x')
        ->assertForbidden();
})->with(['filters', 'allocation', 'record', 'nonexistent']);

it('applies the property, year and method filters on the cost allocation page', function () {
    loginAsAdminInWastePanel();

    Livewire::test(CostAllocationPage::class)
        ->call('applyFilter', 'method', 'equal')
        ->assertSet('method', 'equal')
        ->call('applyFilter', 'year', 2024)
        ->assertSet('year', 2024);
});
