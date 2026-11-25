<?php

use App\Enums\MaintenanceIntervalUnit;
use App\Filament\Admin\Resources\OrganizationResource\Pages\CreateOrganization;
use App\Filament\Fm\Resources\MaintenancePlanTemplateResource\Pages\ListMaintenancePlanTemplates;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\MaintenancePlan;
use App\Models\MaintenancePlanTemplate;
use App\Models\Organization;
use App\Services\MaintenanceCatalogImporter;
use Filament\Livewire\Topbar;
use Livewire\Livewire;

it('ships valid catalogs for all supported countries', function () {
    foreach (MaintenanceCatalogImporter::COUNTRIES as $country) {
        $entries = require database_path("seeders/data/maintenance_catalogs/{$country}.php");

        expect($entries)->not->toBeEmpty();

        foreach ($entries as $entry) {
            expect($entry)->toHaveKeys(['asset_type', 'name', 'type', 'interval']);
            expect($entry['interval'][0])->toBeInt()->toBeGreaterThan(0);
            expect($entry['interval'][1])->toBeInstanceOf(MaintenanceIntervalUnit::class);
        }
    }
});

it('imports a country catalog as templates and generates plans for existing assets', function () {
    loginAsAdminInFmPanel();

    $aufzug = Asset::factory()->create([
        'asset_type_id' => AssetType::query()->firstOrCreate(['name' => 'Aufzug'])->id,
    ]);

    $imported = app(MaintenanceCatalogImporter::class)->import(defaultOrganization(), 'de');

    expect($imported)->toBeGreaterThan(10);
    expect(MaintenancePlanTemplate::query()->where('country', 'DE')->count())->toBe($imported);

    expect(MaintenancePlan::query()->where('asset_id', $aufzug->id)->count())->toBe(2);
    expect(MaintenancePlan::query()->where('asset_id', $aufzug->id)->pluck('legal_basis')->unique()->first())->toBe('BetrSichV §16');

    app(MaintenanceCatalogImporter::class)->import(defaultOrganization(), 'de');
    expect(MaintenancePlan::query()->where('asset_id', $aufzug->id)->count())->toBe(2);
});

it('generates plans automatically when an asset is linked to an asset type', function () {
    loginAsAdminInFmPanel();

    app(MaintenanceCatalogImporter::class)->import(defaultOrganization(), 'de');

    $fireExtinguisherType = AssetType::query()->firstOrCreate(['name' => 'Feuerlöscher']);

    $asset = Asset::factory()->create(['asset_type_id' => $fireExtinguisherType->id]);

    $plan = MaintenancePlan::query()->where('asset_id', $asset->id)->firstOrFail();

    expect($plan->name)->toBe('Sachkundigenprüfung Feuerlöscher');
    expect($plan->organization_id)->toBe(defaultOrganization()->getKey());
    expect($plan->next_due_at->toDateString())->toBe(now()->addYears(2)->toDateString());
});

it('generates plans for existing assets when a template is created manually', function () {
    loginAsAdminInFmPanel();

    $typ = AssetType::query()->firstOrCreate(['name' => 'Klimaanlage']);
    $asset = Asset::factory()->create(['asset_type_id' => $typ->id]);

    MaintenancePlanTemplate::factory()->create([
        'asset_type_id' => $typ->id,
        'name' => 'Kältemittel-Dichtheitsprüfung',
    ]);

    expect(MaintenancePlan::query()->where('asset_id', $asset->id)->where('name', 'Kältemittel-Dichtheitsprüfung')->exists())->toBeTrue();
});

it('keeps catalogs tenant-isolated', function () {
    loginAsAdminInFmPanel();

    app(MaintenanceCatalogImporter::class)->import(defaultOrganization(), 'de');

    $fremdeOrganisation = Organization::factory()->create();

    $fremdesAsset = inOrganization($fremdeOrganisation, fn (): Asset => Asset::factory()->create([
        'asset_type_id' => AssetType::query()->firstOrCreate(['name' => 'Aufzug'])->id,
    ]));

    expect(MaintenancePlan::query()->withoutGlobalScopes()->where('asset_id', $fremdesAsset->id)->count())->toBe(0);
});

it('imports the selected catalog when registering a tenant from the tenant menu', function () {
    loginAsAdmin();

    Livewire::test(Topbar::class)
        ->callAction('registerOrganization', data: [
            'name' => 'Standort Warschau',
            'default_locale' => 'de',
            'maintenance_catalog' => 'pl',
        ])
        ->assertRedirect();

    $organization = Organization::query()->where('slug', 'standort-warschau')->firstOrFail();

    expect(MaintenancePlanTemplate::query()
        ->withoutGlobalScopes()
        ->where('organization_id', $organization->getKey())
        ->where('country', 'PL')
        ->count())->toBeGreaterThan(5);
});

it('imports the selected catalog when creating a tenant in the admin panel', function () {
    loginAsAdminInAdminPanel();

    Livewire::test(CreateOrganization::class)
        ->fillForm([
            'name' => 'Standort Paris',
            'slug' => 'standort-paris',
            'default_locale' => 'de',
            'maintenance_catalog' => 'fr',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $organization = Organization::query()->where('slug', 'standort-paris')->firstOrFail();

    expect(MaintenancePlanTemplate::query()
        ->withoutGlobalScopes()
        ->where('organization_id', $organization->getKey())
        ->where('country', 'FR')
        ->count())->toBeGreaterThan(5);
});

it('lists imported templates in the catalog resource', function () {
    loginAsAdminInFmPanel();

    app(MaintenanceCatalogImporter::class)->import(defaultOrganization(), 'de');

    Livewire::test(ListMaintenancePlanTemplates::class)
        ->assertSee('Hauptprüfung durch ZÜS')
        ->assertSee('DE');
});

it('imports a catalog into an existing tenant via the catalog page action', function () {
    loginAsAdminInFmPanel();

    Livewire::test(ListMaintenancePlanTemplates::class)
        ->callAction('importCatalog', data: ['country' => 'fr'])
        ->assertHasNoActionErrors();

    expect(MaintenancePlanTemplate::query()->where('country', 'FR')->count())->toBeGreaterThan(5);
});
