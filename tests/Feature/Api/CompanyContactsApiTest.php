<?php

use App\Models\Company;
use App\Models\CompanyContact;
use App\Models\Organization;
use Filament\Facades\Filament;

beforeEach(function (): void {
    Filament::setTenant(Organization::factory()->create(), isQuiet: true);
});

it('lists the contacts of a company with filters and sorting', function () {
    $company = Company::factory()->create();
    $primary = $company->contacts()->create(['first_name' => 'Anna', 'last_name' => 'Zimmer', 'email' => 'anna@service.example', 'is_primary' => true]);
    $other = $company->contacts()->create(['first_name' => 'Bernd', 'last_name' => 'Adler', 'email' => 'bernd@service.example']);
    Company::factory()->create()->contacts()->create(['last_name' => 'Fremd']);

    actingAsApiUser(['companies:read']);

    $this->getJson('/api/v1/companies/'.$company->getKey().'/contacts')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $primary->getKey())
        ->assertJsonStructure(['data' => [['id', 'company_id', 'first_name', 'last_name', 'email', 'is_primary', 'links' => ['self']]], 'links', 'meta']);

    $this->getJson('/api/v1/companies/'.$company->getKey().'/contacts?filter[search]=bernd@')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $other->getKey());
    $this->getJson('/api/v1/companies/'.$company->getKey().'/contacts?filter[last_name]=zim')->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/companies/'.$company->getKey().'/contacts?filter[is_primary]=false')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $other->getKey());
    $this->getJson('/api/v1/companies/'.$company->getKey().'/contacts?sort=last_name')->assertJsonPath('data.0.last_name', 'Adler');
});

it('shows, updates and deletes a single contact', function () {
    $company = Company::factory()->create();
    $primary = $company->contacts()->create(['last_name' => 'Zimmer', 'is_primary' => true]);
    $contact = $company->contacts()->create(['last_name' => 'Adler']);

    actingAsApiUser(['companies:write']);

    $this->getJson('/api/v1/contacts/'.$contact->getKey())
        ->assertOk()
        ->assertJsonPath('data.last_name', 'Adler')
        ->assertJsonPath('data.company_id', $company->getKey());

    $this->patchJson('/api/v1/contacts/'.$contact->getKey(), ['last_name' => '', 'email' => 'keine-mail'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['last_name', 'email']);

    $this->patchJson('/api/v1/contacts/'.$contact->getKey(), ['position' => 'Leitung', 'mobile' => '+49 170 1', 'is_primary' => true])
        ->assertOk()
        ->assertJsonPath('data.position', 'Leitung')
        ->assertJsonPath('data.is_primary', true);

    expect($primary->refresh()->is_primary)->toBeFalse();

    $this->deleteJson('/api/v1/contacts/'.$contact->getKey())->assertNoContent();

    expect(CompanyContact::query()->find($contact->getKey()))->toBeNull();
});

it('creates contacts for a company of the tenant', function () {
    $company = Company::factory()->create();
    $fremd = inOrganization(Organization::factory()->create(), fn (): Company => Company::factory()->create());

    actingAsApiUser(['companies:write']);

    $this->postJson('/api/v1/companies/'.$fremd->getKey().'/contacts', ['last_name' => 'X'])->assertNotFound();

    $this->postJson('/api/v1/companies/'.$company->getKey().'/contacts', ['first_name' => 'Ohne'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['last_name']);

    $response = $this->postJson('/api/v1/companies/'.$company->getKey().'/contacts', [
        'first_name' => 'Max',
        'last_name' => 'Muster',
        'position' => 'Techniker',
        'email' => 'max@service.example',
        'phone' => '+49 30 1',
        'is_primary' => true,
        'notes' => 'Erreichbar vormittags',
    ])->assertCreated()
        ->assertJsonPath('data.company_id', $company->getKey())
        ->assertJsonPath('data.last_name', 'Muster')
        ->assertJsonPath('data.is_primary', true);

    expect(CompanyContact::query()->find($response->json('data.id'))?->company_id)->toBe($company->getKey());
});

it('hides contacts of companies in other organizations', function () {
    $fremd = inOrganization(Organization::factory()->create(), function (): CompanyContact {
        return Company::factory()->create()->contacts()->create(['last_name' => 'Fremd']);
    });

    actingAsApiUser(['companies:write']);

    $this->getJson('/api/v1/companies/'.$fremd->company_id.'/contacts')->assertNotFound();
    $this->getJson('/api/v1/contacts/'.$fremd->getKey())->assertNotFound();
    $this->patchJson('/api/v1/contacts/'.$fremd->getKey(), ['last_name' => 'X'])->assertNotFound();
    $this->deleteJson('/api/v1/contacts/'.$fremd->getKey())->assertNotFound();

    expect(CompanyContact::query()->find($fremd->getKey()))->not->toBeNull();
});

it('refuses writes with a read-only ability', function () {
    $company = Company::factory()->create();
    $contact = $company->contacts()->create(['last_name' => 'Muster']);

    actingAsApiUser(['companies:read']);

    $this->getJson('/api/v1/contacts/'.$contact->getKey())->assertOk();
    $this->postJson('/api/v1/companies/'.$company->getKey().'/contacts', ['last_name' => 'X'])->assertForbidden();
    $this->patchJson('/api/v1/contacts/'.$contact->getKey(), ['last_name' => 'X'])->assertForbidden();
    $this->deleteJson('/api/v1/contacts/'.$contact->getKey())->assertForbidden();
});
