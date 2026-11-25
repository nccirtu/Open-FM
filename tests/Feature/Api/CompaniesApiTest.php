<?php

use App\Models\Asset;
use App\Models\Company;
use App\Models\Organization;
use Filament\Facades\Filament;

beforeEach(function (): void {
    Filament::setTenant(Organization::factory()->create(), isQuiet: true);
});

it('lists companies with filters, sorting and includes', function () {
    $service = Company::factory()->create(['name' => 'Service GmbH', 'role' => 'supplier', 'email' => 'info@service.example', 'vat_id' => 'DE123456789']);
    $kunde = Company::factory()->create(['name' => 'Kunde AG', 'role' => 'client', 'email' => 'kontakt@kunde.example', 'vat_id' => 'ATU99999999']);
    $service->addresses()->create(['type' => 'billing', 'city' => 'Berlin', 'is_primary' => true]);
    $service->contacts()->create(['last_name' => 'Muster', 'is_primary' => true]);

    actingAsApiUser(['companies:read']);

    $this->getJson('/api/v1/companies')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', 'Kunde AG')
        ->assertJsonStructure(['data' => [['id', 'name', 'role', 'email', 'vat_id', 'links' => ['self']]], 'links', 'meta']);

    $this->getJson('/api/v1/companies?filter[search]=ATU')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $kunde->getKey());
    $this->getJson('/api/v1/companies?filter[search]=info@')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $service->getKey());
    $this->getJson('/api/v1/companies?filter[name]=serv')->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/companies?filter[role]=client')->assertJsonCount(1, 'data')->assertJsonPath('data.0.role', 'client');
    $this->getJson('/api/v1/companies?sort=-name')->assertJsonPath('data.0.name', 'Service GmbH');

    $this->getJson('/api/v1/companies?include=addresses,contacts&filter[name]=service')
        ->assertOk()
        ->assertJsonPath('data.0.addresses.0.city', 'Berlin')
        ->assertJsonPath('data.0.contacts.0.last_name', 'Muster');
});

it('shows a single company with requested relations', function () {
    $company = Company::factory()->create(['name' => 'Service GmbH']);
    $company->contacts()->create(['last_name' => 'Muster']);

    actingAsApiUser(['companies:read']);

    $this->getJson('/api/v1/companies/'.$company->getKey().'?include=contacts')
        ->assertOk()
        ->assertJsonPath('data.name', 'Service GmbH')
        ->assertJsonPath('data.contacts.0.last_name', 'Muster');

    $this->getJson('/api/v1/companies/'.$company->getKey().'?include=nope')->assertStatus(400);
});

it('hides companies of other organizations', function () {
    $company = Company::factory()->create();
    $fremd = inOrganization(Organization::factory()->create(), fn (): Company => Company::factory()->create());

    actingAsApiUser(['companies:read']);

    $this->getJson('/api/v1/companies')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $company->getKey());
    $this->getJson('/api/v1/companies/'.$fremd->getKey())->assertNotFound();
});

it('creates, updates and deletes companies', function () {
    Company::factory()->create(['name' => 'Service GmbH']);
    inOrganization(Organization::factory()->create(), fn (): Company => Company::factory()->create(['name' => 'Neue GmbH']));

    actingAsApiUser(['companies:write']);

    $this->postJson('/api/v1/companies', ['name' => 'Service GmbH'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);

    $this->postJson('/api/v1/companies', ['name' => 'X', 'role' => 'partner', 'email' => 'keine-mail'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['role', 'email']);

    $response = $this->postJson('/api/v1/companies', [
        'name' => 'Neue GmbH',
        'role' => 'both',
        'email' => 'info@neue.example',
        'phone' => '+49 30 123456',
        'website' => 'https://neue.example',
        'vat_id' => 'DE999999999',
        'iban' => 'DE02120300000000202051',
        'notes' => 'Test',
    ])->assertCreated()
        ->assertJsonPath('data.name', 'Neue GmbH')
        ->assertJsonPath('data.role', 'both')
        ->assertJsonPath('data.iban', 'DE02120300000000202051');

    $company = Company::query()->findOrFail($response->json('data.id'));

    expect($company->organization_id)->toBe(Filament::getTenant()->getKey());

    $this->patchJson('/api/v1/companies/'.$company->getKey(), ['name' => 'Service GmbH'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);

    $this->patchJson('/api/v1/companies/'.$company->getKey(), ['role' => 'client', 'notes' => null])
        ->assertOk()
        ->assertJsonPath('data.role', 'client')
        ->assertJsonPath('data.notes', null)
        ->assertJsonPath('data.name', 'Neue GmbH');

    $this->deleteJson('/api/v1/companies/'.$company->getKey())->assertNoContent();

    expect(Company::query()->find($company->getKey()))->toBeNull();
});

it('refuses to delete companies that are still in use', function () {
    $company = Company::factory()->create();
    Asset::factory()->create(['company_id' => $company->getKey()]);

    actingAsApiUser(['companies:write']);

    $this->deleteJson('/api/v1/companies/'.$company->getKey())
        ->assertStatus(409)
        ->assertJsonPath('message', __('api.errors.company_in_use'));
});

it('refuses writes with a read-only ability', function () {
    $company = Company::factory()->create();

    actingAsApiUser(['companies:read']);

    $this->postJson('/api/v1/companies', ['name' => 'X'])->assertForbidden();
    $this->patchJson('/api/v1/companies/'.$company->getKey(), ['name' => 'X'])->assertForbidden();
    $this->deleteJson('/api/v1/companies/'.$company->getKey())->assertForbidden();
});
