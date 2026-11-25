<?php

use App\Models\Company;
use App\Models\CompanyAddress;
use App\Models\Organization;
use Filament\Facades\Filament;

beforeEach(function (): void {
    Filament::setTenant(Organization::factory()->create(), isQuiet: true);
});

it('lists the addresses of a company with filters and sorting', function () {
    $company = Company::factory()->create();
    $billing = $company->addresses()->create(['type' => 'billing', 'city' => 'Berlin', 'is_primary' => true]);
    $site = $company->addresses()->create(['type' => 'site', 'city' => 'Hamburg']);
    Company::factory()->create()->addresses()->create(['type' => 'site', 'city' => 'Andere']);

    actingAsApiUser(['companies:read']);

    $this->getJson('/api/v1/companies/'.$company->getKey().'/addresses')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $billing->getKey())
        ->assertJsonStructure(['data' => [['id', 'company_id', 'type', 'city', 'is_primary', 'links' => ['self']]], 'links', 'meta']);

    $this->getJson('/api/v1/companies/'.$company->getKey().'/addresses?filter[type]=site')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $site->getKey());
    $this->getJson('/api/v1/companies/'.$company->getKey().'/addresses?filter[city]=hamb')->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/companies/'.$company->getKey().'/addresses?filter[is_primary]=true')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $billing->getKey());
    $this->getJson('/api/v1/companies/'.$company->getKey().'/addresses?sort=-city')->assertJsonPath('data.0.city', 'Hamburg');
});

it('shows, updates and deletes a single address', function () {
    $company = Company::factory()->create();
    $primary = $company->addresses()->create(['type' => 'billing', 'city' => 'Berlin', 'is_primary' => true]);
    $address = $company->addresses()->create(['type' => 'site', 'city' => 'Hamburg']);

    actingAsApiUser(['companies:write']);

    $this->getJson('/api/v1/addresses/'.$address->getKey())
        ->assertOk()
        ->assertJsonPath('data.city', 'Hamburg')
        ->assertJsonPath('data.company_id', $company->getKey());

    $this->patchJson('/api/v1/addresses/'.$address->getKey(), ['type' => 'home'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['type']);

    $this->patchJson('/api/v1/addresses/'.$address->getKey(), ['street' => 'Hafenstraße 1', 'country' => 'DE', 'is_primary' => true])
        ->assertOk()
        ->assertJsonPath('data.street', 'Hafenstraße 1')
        ->assertJsonPath('data.is_primary', true);

    expect($primary->refresh()->is_primary)->toBeFalse();

    $this->deleteJson('/api/v1/addresses/'.$address->getKey())->assertNoContent();

    expect(CompanyAddress::query()->find($address->getKey()))->toBeNull();
});

it('creates addresses for a company of the tenant', function () {
    $company = Company::factory()->create();
    $fremd = inOrganization(Organization::factory()->create(), fn (): Company => Company::factory()->create());

    actingAsApiUser(['companies:write']);

    $this->postJson('/api/v1/companies/'.$fremd->getKey().'/addresses', ['type' => 'billing', 'city' => 'X'])->assertNotFound();

    $this->postJson('/api/v1/companies/'.$company->getKey().'/addresses', ['type' => 'billing', 'country' => 'Deutschland'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['country']);

    $response = $this->postJson('/api/v1/companies/'.$company->getKey().'/addresses', [
        'type' => 'shipping',
        'label' => 'Lager',
        'street' => 'Industriestraße 9',
        'zip_code' => '10115',
        'city' => 'Berlin',
        'country' => 'DE',
        'is_primary' => true,
    ])->assertCreated()
        ->assertJsonPath('data.company_id', $company->getKey())
        ->assertJsonPath('data.type', 'shipping')
        ->assertJsonPath('data.is_primary', true);

    expect(CompanyAddress::query()->find($response->json('data.id'))?->company_id)->toBe($company->getKey());
});

it('hides addresses of companies in other organizations', function () {
    $fremd = inOrganization(Organization::factory()->create(), function (): CompanyAddress {
        return Company::factory()->create()->addresses()->create(['type' => 'billing', 'city' => 'Fremd']);
    });

    actingAsApiUser(['companies:write']);

    $this->getJson('/api/v1/companies/'.$fremd->company_id.'/addresses')->assertNotFound();
    $this->getJson('/api/v1/addresses/'.$fremd->getKey())->assertNotFound();
    $this->patchJson('/api/v1/addresses/'.$fremd->getKey(), ['city' => 'X'])->assertNotFound();
    $this->deleteJson('/api/v1/addresses/'.$fremd->getKey())->assertNotFound();

    expect(CompanyAddress::query()->find($fremd->getKey()))->not->toBeNull();
});

it('refuses writes with a read-only ability', function () {
    $company = Company::factory()->create();
    $address = $company->addresses()->create(['type' => 'billing']);

    actingAsApiUser(['companies:read']);

    $this->getJson('/api/v1/addresses/'.$address->getKey())->assertOk();
    $this->postJson('/api/v1/companies/'.$company->getKey().'/addresses', ['type' => 'billing'])->assertForbidden();
    $this->patchJson('/api/v1/addresses/'.$address->getKey(), ['city' => 'X'])->assertForbidden();
    $this->deleteJson('/api/v1/addresses/'.$address->getKey())->assertForbidden();
});
