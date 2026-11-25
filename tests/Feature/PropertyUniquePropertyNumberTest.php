<?php

use App\Filament\Resources\PropertyResource\Pages\CreateProperty;
use App\Filament\Resources\PropertyResource\Pages\EditProperty;
use App\Models\Property;
use Livewire\Livewire;

beforeEach(function () {
    loginAsAdmin();
});

it('rejects a duplicate Objektnummer on the Property create page', function () {
    Property::create(['property_number' => '5760', 'city' => 'Paderborn', 'country' => 'Deutschland']);

    Livewire::test(CreateProperty::class)
        ->fillForm(['property_number' => '5760', 'city' => 'Paderborn'])
        ->call('create')
        ->assertHasFormErrors(['property_number' => ['unique']]);
});

it('allows saving a Property without changing its own Objektnummer', function () {
    $property = Property::create(['property_number' => '5760', 'city' => 'Paderborn', 'country' => 'Deutschland']);

    Livewire::test(EditProperty::class, ['record' => $property->getRouteKey()])
        ->fillForm(['city' => 'Paderborn-Elsen'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($property->fresh()->city)->toBe('Paderborn-Elsen');
});
