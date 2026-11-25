<?php

use App\Enums\CompanyRole;
use App\Filament\Fm\Resources\CompanyResource\Pages\CreateCompany;
use App\Filament\Fm\Resources\CompanyResource\Pages\EditCompany;
use App\Filament\Fm\Resources\CompanyResource\RelationManagers\AddressesRelationManager;
use App\Filament\Fm\Resources\CompanyResource\RelationManagers\ContactsRelationManager;
use App\Models\Asset;
use App\Models\Company;
use App\Models\Property;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

it('creates a company with role, addresses and contacts in the FM panel', function () {
    loginAsAdminInFmPanel();

    Livewire::test(CreateCompany::class)
        ->fillForm(['name' => 'Elektro Müller GmbH', 'role' => 'both', 'email' => 'info@mueller.test', 'vat_id' => 'DE123456789', 'iban' => 'DE02120300000000202051'])
        ->call('create')
        ->assertHasNoFormErrors();

    $company = Company::query()->where('name', 'Elektro Müller GmbH')->firstOrFail();

    expect($company->role)->toBe(CompanyRole::Both)
        ->and($company->role->isSupplier())->toBeTrue()
        ->and($company->role->isClient())->toBeTrue()
        ->and($company->organization_id)->toBe(defaultOrganization()->getKey());

    Livewire::test(AddressesRelationManager::class, ['ownerRecord' => $company, 'pageClass' => EditCompany::class])
        ->callAction(TestAction::make('create')->table(), ['type' => 'billing', 'street' => 'Hauptstraße 1', 'zip_code' => '50667', 'city' => 'Köln', 'country' => 'DE', 'is_primary' => true])
        ->assertHasNoActionErrors()
        ->callAction(TestAction::make('create')->table(), ['type' => 'site', 'street' => 'Lagerweg 5', 'zip_code' => '50668', 'city' => 'Köln', 'country' => 'DE', 'is_primary' => true])
        ->assertHasNoActionErrors();

    Livewire::test(ContactsRelationManager::class, ['ownerRecord' => $company, 'pageClass' => EditCompany::class])
        ->callAction(TestAction::make('create')->table(), ['first_name' => 'Anna', 'last_name' => 'Müller', 'position' => 'Geschäftsführung', 'email' => 'anna@mueller.test', 'is_primary' => true])
        ->assertHasNoActionErrors();

    $company = $company->fresh();

    expect($company->addresses()->count())->toBe(2)
        ->and($company->addresses()->where('is_primary', true)->count())->toBe(1)
        ->and($company->primaryAddress->street)->toBe('Lagerweg 5')
        ->and($company->primaryContact->fullName())->toBe('Anna Müller')
        ->and(Company::query()->suppliers()->count())->toBe(1)
        ->and(Company::query()->clients()->count())->toBe(1);
});

it('keeps the maintenance partner link on assets under the new company model', function () {
    loginAsAdmin();
    $company = Company::factory()->create(['name' => 'Aufzug Service AG']);
    $asset = Asset::create(['property_id' => Property::factory()->create()->getKey(), 'name' => 'Lift', 'company_id' => $company->getKey()]);

    expect($asset->company->is($company))->toBeTrue()
        ->and($company->assets()->count())->toBe(1)
        ->and($company->fresh()->role)->toBe(CompanyRole::Supplier);
});
