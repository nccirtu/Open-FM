<?php

use App\Models\Asset;
use App\Models\Company;
use App\Models\Contract;
use App\Models\MaintenancePlan;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Ticket;
use Filament\Facades\Filament;

beforeEach(function (): void {
    Filament::setTenant(Organization::factory()->create(), isQuiet: true);
});

it('lists contracts with filters, sorting and includes', function () {
    $company = Company::factory()->create(['name' => 'Kälte KG']);
    $property = Property::factory()->create(['property_number' => '5000']);
    $treffer = Contract::factory()->create([
        'name' => 'Kältewartung',
        'contract_number' => 'KW-2026',
        'partner_name' => 'Kälte KG',
        'company_id' => $company->getKey(),
        'type' => 'maintenance',
        'status' => 'active',
        'end_date' => '2026-06-30',
    ]);
    $treffer->properties()->attach($property);
    Contract::factory()->create(['name' => 'Bürostrom', 'contract_number' => 'ST-1', 'partner_name' => 'Stadtwerke', 'type' => 'utility', 'status' => 'terminated', 'end_date' => '2027-12-31']);

    actingAsApiUser(['contracts:read']);

    $this->getJson('/api/v1/contracts')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', 'Bürostrom')
        ->assertJsonStructure(['data' => [['id', 'name', 'contract_number', 'partner_name', 'type', 'status', 'start_date', 'end_date', 'notice_deadline', 'amount', 'billing_cycle', 'links' => ['self']]], 'links', 'meta']);

    $this->getJson('/api/v1/contracts?filter[search]=kw-20')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $treffer->getKey());
    $this->getJson('/api/v1/contracts?filter[search]=stadtwerke')->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Bürostrom');
    $this->getJson('/api/v1/contracts?filter[status]=active')->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/contracts?filter[type]=utility')->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/contracts?filter[company_id]='.$company->getKey())->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/contracts?filter[property_id]='.$property->getKey())->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $treffer->getKey());
    $this->getJson('/api/v1/contracts?filter[ends_before]=2026-12-31')->assertJsonCount(1, 'data')->assertJsonPath('data.0.end_date', '2026-06-30');
    $this->getJson('/api/v1/contracts?sort=-name')->assertJsonPath('data.0.name', 'Kältewartung');
    $this->getJson('/api/v1/contracts?sort=-end_date')->assertJsonPath('data.0.name', 'Bürostrom');

    $this->getJson('/api/v1/contracts?include=company,properties&filter[status]=active')
        ->assertOk()
        ->assertJsonPath('data.0.company.name', 'Kälte KG')
        ->assertJsonPath('data.0.properties.0.property_number', '5000');
});

it('shows a single contract with assets', function () {
    $asset = Asset::factory()->create(['name' => 'Kühlzelle']);
    $contract = Contract::factory()->create(['end_date' => '2026-12-31', 'notice_period_days' => 90]);
    $contract->assets()->attach($asset);

    actingAsApiUser(['contracts:read']);

    $this->getJson('/api/v1/contracts/'.$contract->getKey().'?include=assets')
        ->assertOk()
        ->assertJsonPath('data.id', $contract->getKey())
        ->assertJsonPath('data.notice_deadline', '2026-10-02')
        ->assertJsonPath('data.assets.0.name', 'Kühlzelle');

    $this->getJson('/api/v1/contracts/'.$contract->getKey().'?include=files')->assertStatus(400);
});

it('hides contracts of other organizations', function () {
    $contract = Contract::factory()->create();
    $fremd = inOrganization(Organization::factory()->create(), fn (): Contract => Contract::factory()->create());

    actingAsApiUser(['contracts:read']);

    $this->getJson('/api/v1/contracts')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $contract->getKey());
    $this->getJson('/api/v1/contracts/'.$fremd->getKey())->assertNotFound();
});

it('creates, updates and deletes contracts and syncs properties and assets', function () {
    $company = Company::factory()->create();
    $property = Property::factory()->create();
    $asset = Asset::factory()->create();
    $fremdeFirma = inOrganization(Organization::factory()->create(), fn (): Company => Company::factory()->create());
    $fremdesObjekt = inOrganization(Organization::factory()->create(), fn (): Property => Property::factory()->create());
    $fremdeAnlage = inOrganization(Organization::factory()->create(), fn (): Asset => Asset::factory()->create());

    actingAsApiUser(['contracts:write']);

    $this->postJson('/api/v1/contracts', ['name' => 'x', 'partner_name' => 'y', 'company_id' => $fremdeFirma->getKey(), 'property_ids' => [$fremdesObjekt->getKey()], 'asset_ids' => [$fremdeAnlage->getKey()]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['company_id', 'property_ids.0', 'asset_ids.0']);

    $this->postJson('/api/v1/contracts', ['name' => 'x', 'start_date' => '2026-06-01', 'end_date' => '2026-01-01', 'type' => 'lease', 'billing_cycle' => 'weekly'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['partner_name', 'end_date', 'type', 'billing_cycle']);

    $response = $this->postJson('/api/v1/contracts', [
        'name' => 'Reinigungsvertrag',
        'contract_number' => 'R-1',
        'partner_name' => 'Blitzblank GmbH',
        'company_id' => $company->getKey(),
        'type' => 'service',
        'start_date' => '2026-01-01',
        'end_date' => '2027-12-31',
        'notice_period_days' => 60,
        'amount' => 850.5,
        'billing_cycle' => 'monthly',
        'property_ids' => [$property->getKey()],
        'asset_ids' => [$asset->getKey()],
    ])->assertCreated()
        ->assertJsonPath('data.name', 'Reinigungsvertrag')
        ->assertJsonPath('data.status', 'active')
        ->assertJsonPath('data.billing_cycle', 'monthly')
        ->assertJsonPath('data.notice_deadline', '2027-11-01')
        ->assertJsonPath('data.company.id', $company->getKey())
        ->assertJsonPath('data.properties.0.id', $property->getKey())
        ->assertJsonPath('data.assets.0.id', $asset->getKey());

    $contract = Contract::query()->findOrFail($response->json('data.id'));

    expect($contract->organization_id)->toBe(Filament::getTenant()->getKey());

    $this->patchJson('/api/v1/contracts/'.$contract->getKey(), ['status' => 'terminated', 'property_ids' => []])
        ->assertOk()
        ->assertJsonPath('data.status', 'terminated')
        ->assertJsonCount(0, 'data.properties')
        ->assertJsonCount(1, 'data.assets');

    $fremd = inOrganization(Organization::factory()->create(), fn (): Contract => Contract::factory()->create());

    $this->patchJson('/api/v1/contracts/'.$fremd->getKey(), ['name' => 'x'])->assertNotFound();
    $this->deleteJson('/api/v1/contracts/'.$fremd->getKey())->assertNotFound();

    $this->deleteJson('/api/v1/contracts/'.$contract->getKey())->assertNoContent();

    expect(Contract::query()->find($contract->getKey()))->toBeNull()
        ->and($asset->refresh()->exists)->toBeTrue();
});

it('refuses to delete contracts referenced by tickets or maintenance plans', function () {
    $withTicket = Contract::factory()->create();
    Ticket::factory()->create(['contract_id' => $withTicket->getKey()]);
    $withPlan = Contract::factory()->create();
    MaintenancePlan::factory()->create(['contract_id' => $withPlan->getKey()]);

    actingAsApiUser(['contracts:write']);

    $this->deleteJson('/api/v1/contracts/'.$withTicket->getKey())->assertStatus(409)->assertJsonPath('message', __('api.errors.contract_in_use'));
    $this->deleteJson('/api/v1/contracts/'.$withPlan->getKey())->assertStatus(409);
});

it('refuses writes with a read-only ability', function () {
    $contract = Contract::factory()->create();

    actingAsApiUser(['contracts:read']);

    $this->getJson('/api/v1/contracts/'.$contract->getKey())->assertOk();
    $this->postJson('/api/v1/contracts', ['name' => 'x', 'partner_name' => 'y'])->assertForbidden();
    $this->patchJson('/api/v1/contracts/'.$contract->getKey(), ['name' => 'x'])->assertForbidden();
    $this->deleteJson('/api/v1/contracts/'.$contract->getKey())->assertForbidden();

    actingAsApiUser(['tickets:write']);

    $this->getJson('/api/v1/contracts')->assertForbidden();
});
