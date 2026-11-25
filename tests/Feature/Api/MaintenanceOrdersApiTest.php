<?php

use App\Models\Asset;
use App\Models\MaintenanceOrder;
use App\Models\MaintenancePlan;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use Filament\Facades\Filament;

beforeEach(function (): void {
    Filament::setTenant(Organization::factory()->create(), isQuiet: true);
});

it('lists maintenance orders with filters, sorting and includes', function () {
    $property = Property::factory()->create();
    $asset = Asset::factory()->create(['property_id' => $property->getKey(), 'name' => 'Kessel']);
    $plan = MaintenancePlan::factory()->create(['asset_id' => $asset->getKey(), 'name' => 'Kesselwartung']);
    $overdue = MaintenanceOrder::factory()->create(['maintenance_plan_id' => $plan->getKey(), 'due_at' => now()->subDays(10)->toDateString()]);
    $completed = MaintenanceOrder::factory()->create(['status' => 'completed', 'due_at' => now()->subDays(20)->toDateString()]);
    $future = MaintenanceOrder::factory()->create(['due_at' => now()->addDays(30)->toDateString()]);

    actingAsApiUser(['maintenance:read']);

    $this->getJson('/api/v1/maintenance-orders')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.id', $completed->getKey())
        ->assertJsonStructure(['data' => [['id', 'maintenance_plan_id', 'asset_id', 'due_at', 'status', 'completed_at', 'links' => ['self']]], 'links', 'meta']);

    $this->getJson('/api/v1/maintenance-orders?filter[status]=completed')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $completed->getKey());
    $this->getJson('/api/v1/maintenance-orders?filter[asset_id]='.$asset->getKey())->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $overdue->getKey());
    $this->getJson('/api/v1/maintenance-orders?filter[maintenance_plan_id]='.$plan->getKey())->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/maintenance-orders?filter[property_id]='.$property->getKey())->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $overdue->getKey());
    $this->getJson('/api/v1/maintenance-orders?filter[due_before]='.now()->toDateString())->assertJsonCount(2, 'data');
    $this->getJson('/api/v1/maintenance-orders?filter[due_after]='.now()->toDateString())->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $future->getKey());
    $this->getJson('/api/v1/maintenance-orders?filter[overdue]=true')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $overdue->getKey());
    $this->getJson('/api/v1/maintenance-orders?filter[overdue]=false')->assertJsonCount(2, 'data');
    $this->getJson('/api/v1/maintenance-orders?sort=-due_at')->assertJsonPath('data.0.id', $future->getKey());
    $this->getJson('/api/v1/maintenance-orders?sort=status')->assertJsonPath('data.0.status', 'completed');

    $this->getJson('/api/v1/maintenance-orders?include=plan,asset&filter[maintenance_plan_id]='.$plan->getKey())
        ->assertOk()
        ->assertJsonPath('data.0.plan.name', 'Kesselwartung')
        ->assertJsonPath('data.0.asset.name', 'Kessel');
});

it('shows a single maintenance order with the completing user', function () {
    $user = User::factory()->create(['name' => 'Peter Prüfer']);
    $order = MaintenanceOrder::factory()->create(['status' => 'completed']);
    $order->forceFill(['completed_by' => $user->getKey(), 'completed_at' => now()])->saveQuietly();

    actingAsApiUser(['maintenance:read']);

    $this->getJson('/api/v1/maintenance-orders/'.$order->getKey().'?include=completedBy')
        ->assertOk()
        ->assertJsonPath('data.id', $order->getKey())
        ->assertJsonPath('data.completed_by.name', 'Peter Prüfer');

    $this->getJson('/api/v1/maintenance-orders/'.$order->getKey().'?include=protocol')->assertStatus(400);
});

it('hides maintenance orders of other organizations', function () {
    $order = MaintenanceOrder::factory()->create();
    $fremd = inOrganization(Organization::factory()->create(), fn (): MaintenanceOrder => MaintenanceOrder::factory()->create());

    actingAsApiUser(['maintenance:read']);

    $this->getJson('/api/v1/maintenance-orders')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $order->getKey());
    $this->getJson('/api/v1/maintenance-orders/'.$fremd->getKey())->assertNotFound();
});

it('creates, completes and deletes maintenance orders with tenant-scoped validation', function () {
    $plan = MaintenancePlan::factory()->create();
    $fremderPlan = inOrganization(Organization::factory()->create(), fn (): MaintenancePlan => MaintenancePlan::factory()->create());
    $fremd = inOrganization(Organization::factory()->create(), fn (): MaintenanceOrder => MaintenanceOrder::factory()->create());

    $user = actingAsApiUser(['maintenance:write']);

    $this->postJson('/api/v1/maintenance-orders', ['maintenance_plan_id' => $fremderPlan->getKey(), 'asset_id' => $fremderPlan->asset_id, 'due_at' => '2026-05-01'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['maintenance_plan_id', 'asset_id']);

    $this->postJson('/api/v1/maintenance-orders', ['maintenance_plan_id' => $plan->getKey(), 'asset_id' => $plan->asset_id, 'due_at' => 'bald', 'status' => 'done'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['due_at', 'status']);

    $response = $this->postJson('/api/v1/maintenance-orders', [
        'maintenance_plan_id' => $plan->getKey(),
        'asset_id' => $plan->asset_id,
        'due_at' => '2026-05-01',
        'notes' => 'Vor Ort anmelden',
        'tasks' => ['Filter tauschen'],
    ])->assertCreated()
        ->assertJsonPath('data.status', 'open')
        ->assertJsonPath('data.due_at', '2026-05-01')
        ->assertJsonPath('data.tasks', ['Filter tauschen'])
        ->assertJsonPath('data.plan.id', $plan->getKey());

    $order = MaintenanceOrder::query()->findOrFail($response->json('data.id'));

    expect($order->organization_id)->toBe(Filament::getTenant()->getKey());

    $this->postJson('/api/v1/maintenance-orders', ['maintenance_plan_id' => $plan->getKey(), 'asset_id' => $plan->asset_id, 'due_at' => '2026-05-01'])
        ->assertStatus(409)
        ->assertJsonPath('message', __('api.errors.maintenance_order_duplicate'));

    $this->patchJson('/api/v1/maintenance-orders/'.$order->getKey(), ['status' => 'completed', 'notes' => 'Alles in Ordnung'])
        ->assertOk()
        ->assertJsonPath('data.status', 'completed')
        ->assertJsonPath('data.completed_by_id', $user->getKey());

    expect($order->refresh()->completed_at)->not->toBeNull();

    $this->patchJson('/api/v1/maintenance-orders/'.$order->getKey(), ['status' => 'open'])
        ->assertOk()
        ->assertJsonPath('data.completed_at', null)
        ->assertJsonPath('data.completed_by_id', null);

    $this->patchJson('/api/v1/maintenance-orders/'.$fremd->getKey(), ['notes' => 'x'])->assertNotFound();
    $this->deleteJson('/api/v1/maintenance-orders/'.$fremd->getKey())->assertNotFound();

    $this->deleteJson('/api/v1/maintenance-orders/'.$order->getKey())->assertNoContent();

    expect(MaintenanceOrder::query()->find($order->getKey()))->toBeNull();
});

it('refuses writes with a read-only ability', function () {
    $order = MaintenanceOrder::factory()->create();

    actingAsApiUser(['maintenance:read']);

    $this->getJson('/api/v1/maintenance-orders/'.$order->getKey())->assertOk();
    $this->postJson('/api/v1/maintenance-orders', ['maintenance_plan_id' => $order->maintenance_plan_id, 'asset_id' => $order->asset_id, 'due_at' => '2026-01-01'])->assertForbidden();
    $this->patchJson('/api/v1/maintenance-orders/'.$order->getKey(), ['status' => 'completed'])->assertForbidden();
    $this->deleteJson('/api/v1/maintenance-orders/'.$order->getKey())->assertForbidden();
});
