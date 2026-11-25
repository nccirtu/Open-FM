<?php

use App\Filament\Resources\ApiTokenResource\Pages\ListApiTokens;
use App\Filament\Resources\PropertyResource;
use App\Filament\Resources\PropertyResource\Pages\ListProperties;
use App\Models\ApiToken;
use App\Models\Asset;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;

function makePropertyInOrganization(Organization $organization, string $propertyNumber, string $city = 'Wien'): Property
{
    return inOrganization($organization, fn (): Property => Property::create([
        'property_number' => $propertyNumber,
        'city' => $city,
        'country' => 'AT',
    ]));
}

it('assigns the current tenant to newly created records', function () {
    loginAsAdmin();

    $property = Property::create(['property_number' => '1001', 'city' => 'Köln', 'country' => 'DE']);
    $asset = Asset::create(['property_id' => $property->id, 'name' => 'Lift']);

    expect($property->organization_id)->toBe(defaultOrganization()->getKey())
        ->and($asset->organization_id)->toBe(defaultOrganization()->getKey());
});

it('only lists records of the current tenant in resource tables', function () {
    loginAsAdmin();

    $eigenerProperty = Property::create(['property_number' => '1001', 'city' => 'Köln', 'country' => 'DE']);

    $fremdeOrganisation = Organization::factory()->create();
    $fremderProperty = makePropertyInOrganization($fremdeOrganisation, '2001');

    expect($fremderProperty->organization_id)->toBe($fremdeOrganisation->getKey());

    Livewire::test(ListProperties::class)
        ->assertCanSeeTableRecords([$eigenerProperty])
        ->assertCanNotSeeTableRecords([$fremderProperty]);
});

it('returns 404 when opening a record of another tenant', function () {
    loginAsAdmin();

    $fremdeOrganisation = Organization::factory()->create();
    $fremderProperty = makePropertyInOrganization($fremdeOrganisation, '2002');

    $this->get(PropertyResource::getUrl('edit', ['record' => $fremderProperty], tenant: defaultOrganization()))
        ->assertNotFound();
});

it('denies access to tenants the user does not belong to', function () {
    $user = loginAsAdmin();

    $fremdeOrganisation = Organization::factory()->create();

    expect($user->canAccessTenant(defaultOrganization()))->toBeTrue();
    expect($user->canAccessTenant($fremdeOrganisation))->toBeFalse();
});

it('scopes roles per tenant', function () {
    $user = loginAsAdmin();

    expect($user->hasRole('Admin'))->toBeTrue();

    $fremdeOrganisation = Organization::factory()->create();

    inOrganization($fremdeOrganisation, function () use ($user): void {
        expect($user->fresh()->hasRole('Admin'))->toBeFalse();
    });
});

it('allows the same property number in different tenants but not twice in one tenant', function () {
    loginAsAdmin();

    Property::create(['property_number' => '4711', 'city' => 'Köln', 'country' => 'DE']);

    $fremdeOrganisation = Organization::factory()->create();
    $fremderProperty = makePropertyInOrganization($fremdeOrganisation, '4711');

    expect($fremderProperty->exists)->toBeTrue();

    Livewire::test(PropertyResource\Pages\CreateProperty::class)
        ->fillForm(['property_number' => '4711', 'city' => 'Bonn', 'country' => 'DE'])
        ->call('create')
        ->assertHasFormErrors(['property_number' => 'unique']);
});

it('scopes the API to the organizations of the token user', function () {
    loginAsAdmin();
    $organisation = defaultOrganization();
    $fremdeOrganisation = Organization::factory()->create();

    $eigenesAsset = inOrganization($organisation, function (): Asset {
        $property = makePropertyInOrganization(defaultOrganization(), '3001');

        return Asset::create(['property_id' => $property->id, 'name' => 'Eigenes Asset']);
    });
    $fremdesAsset = inOrganization($fremdeOrganisation, function () use ($fremdeOrganisation): Asset {
        $property = makePropertyInOrganization($fremdeOrganisation, '3002');

        return Asset::create(['property_id' => $property->id, 'name' => 'Fremdes Asset']);
    });

    actingAsApiUser(['assets:read']);

    $ids = collect($this->getJson('/api/v1/assets')->assertOk()->json('data'))->pluck('id');

    expect($ids)->toContain($eigenesAsset->id)
        ->and($ids)->not->toContain($fremdesAsset->id);

    $this->getJson("/api/v1/assets/{$fremdesAsset->id}")->assertNotFound();
});

it('rejects API requests from users without an organization', function () {
    Sanctum::actingAs(User::factory()->create(), ['assets:read']);

    $this->getJson('/api/v1/assets')->assertForbidden();
});

it('blocks PDF downloads of records from other tenants', function () {
    loginAsAdmin();
    $eigenesObjekt = makePropertyInOrganization(defaultOrganization(), '4001');
    $fremdesObjekt = makePropertyInOrganization(Organization::factory()->create(), '4002');

    $this->get(route('property.dossier.pdf', $eigenesObjekt))->assertSuccessful();
    $this->get(route('property.dossier.pdf', $fremdesObjekt))->assertNotFound();
});

it('redirects guests away from PDF downloads', function () {
    $objekt = makePropertyInOrganization(Organization::factory()->create(), '4003');

    $this->get(route('property.dossier.pdf', $objekt))->assertRedirect();
});

it('assigns API tokens to the current tenant on creation', function () {
    loginAsAdmin();

    Livewire::test(ListApiTokens::class)
        ->mountAction('createToken')
        ->setActionData(['name' => 'Mandanten-Token', 'expires_at' => now()->addDays(30)->format('Y-m-d H:i:s'), 'abilities' => ['assets:read']])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    $token = ApiToken::query()->where('name', 'Mandanten-Token')->firstOrFail();

    expect($token->organization_id)->toBe(defaultOrganization()->getKey());
});
