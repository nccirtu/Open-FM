<?php

use App\Filament\Fm\Resources\CompanyResource\Pages\CreateCompany;
use App\Filament\Fm\Resources\CompanyResource\Pages\ViewCompany;
use App\Models\Company;
use App\Models\Organization;
use Filament\Facades\Filament;
use Livewire\Livewire;

dataset('unsafe websites', [
    'javascript' => 'javascript:alert(1)',
    'javascript with slashes' => 'javascript://example.com/%0Aalert(1)',
    'data' => 'data://text/html,<script>alert(1)</script>',
    'ftp' => 'ftp://example.com/datei',
    'plain text' => 'keine-url',
]);

it('rejects unsafe websites when creating a company through the API', function (string $website) {
    Filament::setTenant(Organization::factory()->create(), isQuiet: true);
    actingAsApiUser(['companies:write']);

    $this->postJson('/api/v1/companies', ['name' => 'Neue GmbH', 'website' => $website])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['website']);

    expect(Company::query()->count())->toBe(0);
})->with('unsafe websites');

it('rejects unsafe websites when updating a company through the API', function (string $website) {
    Filament::setTenant(Organization::factory()->create(), isQuiet: true);
    $company = Company::factory()->create(['website' => 'https://alt.example']);
    actingAsApiUser(['companies:write']);

    $this->patchJson('/api/v1/companies/'.$company->getKey(), ['website' => $website])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['website']);

    expect($company->fresh()->website)->toBe('https://alt.example');
})->with('unsafe websites');

it('accepts http and https websites and clearing the website through the API', function () {
    Filament::setTenant(Organization::factory()->create(), isQuiet: true);
    actingAsApiUser(['companies:write']);

    $id = $this->postJson('/api/v1/companies', ['name' => 'Neue GmbH', 'website' => 'http://neue.example'])
        ->assertCreated()
        ->assertJsonPath('data.website', 'http://neue.example')
        ->json('data.id');

    $this->patchJson('/api/v1/companies/'.$id, ['website' => 'https://neue.example/kontakt'])
        ->assertOk()
        ->assertJsonPath('data.website', 'https://neue.example/kontakt');

    $this->patchJson('/api/v1/companies/'.$id, ['website' => null])
        ->assertOk()
        ->assertJsonPath('data.website', null);
});

it('rejects unsafe websites in the company form', function (string $website) {
    loginAsAdminInFmPanel();

    Livewire::test(CreateCompany::class)
        ->fillForm(['name' => 'Neue GmbH', 'website' => $website])
        ->call('create')
        ->assertHasFormErrors(['website']);
})->with('unsafe websites');

it('never renders a stored javascript website as a link', function () {
    loginAsAdminInFmPanel();

    $company = Company::factory()->create(['website' => 'javascript:alert(1)']);

    Livewire::test(ViewCompany::class, ['record' => $company->getKey()])
        ->assertSuccessful()
        ->assertDontSeeHtml('href="javascript:');

    $safe = Company::factory()->create(['website' => 'https://sicher.example']);

    Livewire::test(ViewCompany::class, ['record' => $safe->getKey()])
        ->assertSeeHtml('href="https://sicher.example"');
});
