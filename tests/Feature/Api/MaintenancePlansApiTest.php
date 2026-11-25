<?php

use App\Models\Asset;
use App\Models\Company;
use App\Models\Contract;
use App\Models\MaintenanceOrder;
use App\Models\MaintenancePlan;
use App\Models\Organization;
use App\Models\Property;
use Filament\Facades\Filament;

beforeEach(function (): void {
    Filament::setTenant(Organization::factory()->create(), isQuiet: true);
});

it('lists maintenance plans with filters, sorting and includes', function () {
    $property = Property::factory()->create(['property_number' => '4000']);
    $asset = Asset::factory()->create(['property_id' => $property->getKey(), 'name' => 'Aufzug A']);
    $company = Company::factory()->create(['name' => 'TÜV Nord']);
    $treffer = MaintenancePlan::factory()->create([
        'asset_id' => $asset->getKey(),
        'name' => 'Aufzugsprüfung',
        'legal_basis' => 'BetrSichV',
        'type' => 'inspection',
        'company_id' => $company->getKey(),
        'next_due_at' => '2026-02-01',
    ]);
    MaintenancePlan::factory()->create(['name' => 'Heizungswartung', 'type' => 'maintenance', 'legal_basis' => null, 'is_active' => false, 'next_due_at' => '2026-06-01']);

    actingAsApiUser(['maintenance:read']);

    $this->getJson('/api/v1/maintenance-plans')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $treffer->getKey())
        ->assertJsonStructure(['data' => [['id', 'asset_id', 'name', 'type', 'interval_value', 'interval_unit', 'next_due_at', 'is_active', 'tasks', 'links' => ['self']]], 'links', 'meta']);

    $this->getJson('/api/v1/maintenance-plans?filter[search]=betrsich')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $treffer->getKey());
    $this->getJson('/api/v1/maintenance-plans?filter[asset_id]='.$asset->getKey())->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/maintenance-plans?filter[property_id]='.$property->getKey())->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $treffer->getKey());
    $this->getJson('/api/v1/maintenance-plans?filter[type]=inspection')->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/maintenance-plans?filter[is_active]=false')->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Heizungswartung');
    $this->getJson('/api/v1/maintenance-plans?filter[company_id]='.$company->getKey())->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/maintenance-plans?filter[due_before]=2026-03-01')->assertJsonCount(1, 'data')->assertJsonPath('data.0.next_due_at', '2026-02-01');
    $this->getJson('/api/v1/maintenance-plans?sort=-name')->assertJsonPath('data.0.name', 'Heizungswartung');
    $this->getJson('/api/v1/maintenance-plans?sort=-next_due_at')->assertJsonPath('data.0.next_due_at', '2026-06-01');

    $this->getJson('/api/v1/maintenance-plans?include=asset,company&filter[type]=inspection')
        ->assertOk()
        ->assertJsonPath('data.0.asset.name', 'Aufzug A')
        ->assertJsonPath('data.0.company.name', 'TÜV Nord');
});

it('shows a single maintenance plan with its orders', function () {
    $plan = MaintenancePlan::factory()->create();
    $order = MaintenanceOrder::factory()->create(['maintenance_plan_id' => $plan->getKey()]);

    actingAsApiUser(['maintenance:read']);

    $this->getJson('/api/v1/maintenance-plans/'.$plan->getKey().'?include=orders')
        ->assertOk()
        ->assertJsonPath('data.id', $plan->getKey())
        ->assertJsonCount(1, 'data.orders')
        ->assertJsonPath('data.orders.0.id', $order->getKey());

    $this->getJson('/api/v1/maintenance-plans/'.$plan->getKey().'?include=template')->assertStatus(400);
});

it('hides maintenance plans of other organizations', function () {
    $plan = MaintenancePlan::factory()->create();
    $fremd = inOrganization(Organization::factory()->create(), fn (): MaintenancePlan => MaintenancePlan::factory()->create());

    actingAsApiUser(['maintenance:read']);

    $this->getJson('/api/v1/maintenance-plans')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $plan->getKey());
    $this->getJson('/api/v1/maintenance-plans/'.$fremd->getKey())->assertNotFound();
});

it('creates, updates and deletes maintenance plans with tenant-scoped validation', function () {
    $asset = Asset::factory()->create();
    $contract = Contract::factory()->create();
    $fremdeAnlage = inOrganization(Organization::factory()->create(), fn (): Asset => Asset::factory()->create());
    $fremderVertrag = inOrganization(Organization::factory()->create(), fn (): Contract => Contract::factory()->create());

    actingAsApiUser(['maintenance:write']);

    $this->postJson('/api/v1/maintenance-plans', ['asset_id' => $fremdeAnlage->getKey(), 'name' => 'x', 'interval_value' => 1, 'next_due_at' => '2026-01-01'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['asset_id']);

    $this->postJson('/api/v1/maintenance-plans', ['asset_id' => $asset->getKey(), 'name' => 'x', 'interval_value' => 0, 'interval_unit' => 'decades', 'next_due_at' => '2026-01-01', 'contract_id' => $fremderVertrag->getKey()])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['interval_value', 'interval_unit', 'contract_id']);

    $response = $this->postJson('/api/v1/maintenance-plans', [
        'asset_id' => $asset->getKey(),
        'name' => 'DGUV V3 Prüfung',
        'type' => 'inspection',
        'legal_basis' => 'DGUV V3',
        'interval_value' => 12,
        'interval_unit' => 'months',
        'next_due_at' => '2026-12-01',
        'lead_time_days' => 30,
        'contract_id' => $contract->getKey(),
        'tasks' => ['Sichtprüfung', 'Messung'],
    ])->assertCreated()
        ->assertJsonPath('data.name', 'DGUV V3 Prüfung')
        ->assertJsonPath('data.type', 'inspection')
        ->assertJsonPath('data.next_due_at', '2026-12-01')
        ->assertJsonPath('data.tasks', ['Sichtprüfung', 'Messung'])
        ->assertJsonPath('data.asset.id', $asset->getKey());

    $plan = MaintenancePlan::query()->findOrFail($response->json('data.id'));

    expect($plan->organization_id)->toBe(Filament::getTenant()->getKey())
        ->and($plan->is_active)->toBeTrue();

    $this->patchJson('/api/v1/maintenance-plans/'.$plan->getKey(), ['is_active' => false, 'next_due_at' => '2027-01-15'])
        ->assertOk()
        ->assertJsonPath('data.is_active', false)
        ->assertJsonPath('data.next_due_at', '2027-01-15');

    $fremd = inOrganization(Organization::factory()->create(), fn (): MaintenancePlan => MaintenancePlan::factory()->create());

    $this->patchJson('/api/v1/maintenance-plans/'.$fremd->getKey(), ['name' => 'x'])->assertNotFound();
    $this->deleteJson('/api/v1/maintenance-plans/'.$fremd->getKey())->assertNotFound();

    $this->deleteJson('/api/v1/maintenance-plans/'.$plan->getKey())->assertNoContent();

    expect(MaintenancePlan::query()->find($plan->getKey()))->toBeNull();
});

it('refuses to delete plans with completed orders', function () {
    $plan = MaintenancePlan::factory()->create();
    MaintenanceOrder::factory()->create(['maintenance_plan_id' => $plan->getKey(), 'status' => 'completed']);

    actingAsApiUser(['maintenance:write']);

    $this->deleteJson('/api/v1/maintenance-plans/'.$plan->getKey())
        ->assertStatus(409)
        ->assertJsonPath('message', __('api.errors.maintenance_plan_in_use'));
});

it('refuses writes with a read-only ability', function () {
    $plan = MaintenancePlan::factory()->create();

    actingAsApiUser(['maintenance:read']);

    $this->getJson('/api/v1/maintenance-plans/'.$plan->getKey())->assertOk();
    $this->postJson('/api/v1/maintenance-plans', ['asset_id' => $plan->asset_id, 'name' => 'x', 'interval_value' => 1, 'next_due_at' => '2026-01-01'])->assertForbidden();
    $this->patchJson('/api/v1/maintenance-plans/'.$plan->getKey(), ['name' => 'x'])->assertForbidden();
    $this->deleteJson('/api/v1/maintenance-plans/'.$plan->getKey())->assertForbidden();

    actingAsApiUser(['tickets:write']);

    $this->getJson('/api/v1/maintenance-plans')->assertForbidden();
});
