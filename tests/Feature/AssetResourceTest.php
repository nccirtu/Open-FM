<?php

use App\Filament\Resources\AssetResource\Pages\CreateAsset;
use App\Filament\Resources\AssetResource\Pages\EditAsset;
use App\Filament\Resources\AssetResource\Pages\ViewAsset;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Company;
use App\Models\Organization;
use App\Models\Property;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    loginAsAdmin();
});

it('shows the partner company fields for every asset type', function () {
    $heizung = AssetType::query()->firstOrCreate(['name' => 'Heizung']);

    Livewire::test(CreateAsset::class)
        ->assertFormFieldIsVisible('company_id')
        ->assertFormFieldIsVisible('inspection_company_id')
        ->fillForm(['asset_type_id' => $heizung->id])
        ->assertFormFieldIsVisible('company_id')
        ->assertFormFieldIsVisible('inspection_company_id');
});

it('persists the maintenance partner and inspection company on an asset', function () {
    $property = Property::create(['property_number' => '12345', 'city' => 'Berlin', 'country' => 'DE']);
    $heizung = AssetType::query()->firstOrCreate(['name' => 'Heizung']);
    $partner = Company::factory()->create(['name' => 'TürTech GmbH']);
    $inspectionCompany = Company::factory()->create(['name' => 'Brandschutz Prüf GmbH']);

    Livewire::test(CreateAsset::class)
        ->fillForm([
            'property_id' => $property->id,
            'name' => 'Eingangstür',
            'asset_type_id' => $heizung->id,
            'company_id' => $partner->id,
            'inspection_company_id' => $inspectionCompany->id,
            'manufacturer' => 'Vaillant',
            'serial_number' => 'SN-42',
            'last_maintenance_at' => '2026-01-15',
            'next_maintenance_at' => '2027-01-15',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $asset = Asset::query()->where('name', 'Eingangstür')->firstOrFail();

    expect($asset->company_id)->toBe($partner->id)
        ->and($asset->company->name)->toBe('TürTech GmbH')
        ->and($asset->inspection_company_id)->toBe($inspectionCompany->id)
        ->and($asset->inspectionCompany->name)->toBe('Brandschutz Prüf GmbH')
        ->and($asset->manufacturer)->toBe('Vaillant')
        ->and($asset->serial_number)->toBe('SN-42')
        ->and($asset->last_maintenance_at->toDateString())->toBe('2026-01-15')
        ->and($asset->next_maintenance_at->toDateString())->toBe('2027-01-15');
});

it('keeps the partner companies when the asset type changes', function () {
    $property = Property::create(['property_number' => '12345', 'city' => 'Berlin', 'country' => 'DE']);
    $tuer = AssetType::query()->firstOrCreate(['name' => 'Automatiktür']);
    $heizung = AssetType::query()->firstOrCreate(['name' => 'Heizung']);
    $partner = Company::factory()->create(['name' => 'TürTech GmbH']);
    $inspectionCompany = Company::factory()->create(['name' => 'Brandschutz Prüf GmbH']);

    $asset = Asset::create([
        'property_id' => $property->id,
        'name' => 'Tür 1',
        'asset_type_id' => $tuer->id,
        'company_id' => $partner->id,
        'inspection_company_id' => $inspectionCompany->id,
    ]);

    Livewire::test(EditAsset::class, ['record' => $asset->getRouteKey()])
        ->fillForm(['asset_type_id' => $heizung->id])
        ->call('save')
        ->assertHasNoFormErrors();

    $asset->refresh();

    expect($asset->asset_type_id)->toBe($heizung->id)
        ->and($asset->company_id)->toBe($partner->id)
        ->and($asset->inspection_company_id)->toBe($inspectionCompany->id);
});

it('shows the partner companies and maintenance dates on the view page', function () {
    $property = Property::create(['property_number' => '12345', 'city' => 'Berlin', 'country' => 'DE']);
    $partner = Company::factory()->create(['name' => 'TürTech GmbH']);
    $inspectionCompany = Company::factory()->create(['name' => 'Brandschutz Prüf GmbH']);

    $asset = Asset::create([
        'property_id' => $property->id,
        'name' => 'Tür 1',
        'asset_type_id' => AssetType::query()->firstOrCreate(['name' => 'Automatiktür'])->id,
        'company_id' => $partner->id,
        'inspection_company_id' => $inspectionCompany->id,
        'manufacturer' => 'Vaillant',
        'next_maintenance_at' => '2027-01-15',
    ]);

    Livewire::test(ViewAsset::class, ['record' => $asset->getRouteKey()])
        ->assertOk()
        ->assertSee('TürTech GmbH')
        ->assertSee('Brandschutz Prüf GmbH')
        ->assertSee('Vaillant')
        ->assertSee('15.01.2027');
});

it('assigns a sequential asset number per organization, independent of other tenants', function () {
    $property = Property::create(['property_number' => '12345', 'city' => 'Berlin', 'country' => 'DE']);
    $heizung = AssetType::query()->firstOrCreate(['name' => 'Heizung']);
    $nextNumber = (Asset::query()->withoutGlobalScopes()->where('organization_id', defaultOrganization()->getKey())->max('asset_number') ?? 0) + 1;

    Livewire::test(CreateAsset::class)
        ->fillForm(['property_id' => $property->id, 'name' => 'Heizung A', 'asset_type_id' => $heizung->id])
        ->call('create')
        ->assertHasNoFormErrors();
    Livewire::test(CreateAsset::class)
        ->fillForm(['property_id' => $property->id, 'name' => 'Heizung B', 'asset_type_id' => $heizung->id])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Asset::query()->where('name', 'Heizung A')->firstOrFail()->asset_number)->toBe($nextNumber)
        ->and(Asset::query()->where('name', 'Heizung B')->firstOrFail()->asset_number)->toBe($nextNumber + 1);

    $other = Organization::factory()->create();
    $otherAsset = inOrganization($other, fn (): Asset => Asset::factory()->create());

    expect($otherAsset->asset_number)->toBe(1);
});

it('uploads and displays an image for an asset', function () {
    Storage::fake('uploads');

    $property = Property::create(['property_number' => '12345', 'city' => 'Berlin', 'country' => 'DE']);
    $heizung = AssetType::query()->firstOrCreate(['name' => 'Heizung']);

    Livewire::test(CreateAsset::class)
        ->fillForm([
            'property_id' => $property->id,
            'name' => 'Heizung mit Bild',
            'asset_type_id' => $heizung->id,
            'image' => UploadedFile::fake()->image('heizung.jpg'),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $asset = Asset::query()->where('name', 'Heizung mit Bild')->firstOrFail();

    expect($asset->image)->not->toBeNull();
    Storage::disk('uploads')->assertExists($asset->image);
});
