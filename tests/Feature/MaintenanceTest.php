<?php

use App\Enums\MaintenanceIntervalUnit;
use App\Enums\MaintenanceOrderStatus;
use App\Enums\MaintenanceType;
use App\Enums\TicketType;
use App\Filament\Fm\Resources\MaintenanceOrderResource;
use App\Filament\Fm\Resources\MaintenanceOrderResource\Pages\ListMaintenanceOrders;
use App\Filament\Fm\Resources\MaintenancePlanResource;
use App\Filament\Fm\Resources\MaintenancePlanResource\Pages\CreateMaintenancePlan;
use App\Filament\Fm\Resources\MaintenancePlanResource\Pages\ListMaintenancePlans;
use App\Filament\Fm\Resources\MaintenancePlanTemplateResource;
use App\Filament\Fm\Widgets\MaintenanceStatsWidget;
use App\Models\Asset;
use App\Models\MaintenanceOrder;
use App\Models\MaintenancePlan;
use App\Models\Organization;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

it('creates a maintenance plan with tenant assignment', function () {
    loginAsAdminInFmPanel();

    $asset = Asset::factory()->create();

    Livewire::test(CreateMaintenancePlan::class)
        ->fillForm([
            'asset_id' => $asset->id,
            'name' => 'DGUV V3 Prüfung',
            'type' => MaintenanceType::Inspection->value,
            'legal_basis' => 'DGUV V3',
            'interval_value' => 12,
            'interval_unit' => MaintenanceIntervalUnit::Months->value,
            'next_due_at' => now()->addMonth()->toDateString(),
            'lead_time_days' => 14,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $plan = MaintenancePlan::query()->firstOrFail();

    expect($plan->organization_id)->toBe(defaultOrganization()->getKey());
    expect($plan->type)->toBe(MaintenanceType::Inspection);
});

it('generates orders for plans within the lead time and skips duplicates', function () {
    loginAsAdminInFmPanel();

    $faellig = MaintenancePlan::factory()->create([
        'next_due_at' => now()->addDays(10)->toDateString(),
        'lead_time_days' => 14,
    ]);

    $nochNichtFaellig = MaintenancePlan::factory()->create([
        'next_due_at' => now()->addDays(60)->toDateString(),
        'lead_time_days' => 14,
    ]);

    $inaktiv = MaintenancePlan::factory()->create([
        'next_due_at' => now()->toDateString(),
        'is_active' => false,
    ]);

    $this->artisan('maintenance:generate-orders')->assertSuccessful();
    $this->artisan('maintenance:generate-orders')->assertSuccessful();

    expect(MaintenanceOrder::query()->count())->toBe(1);

    $order = MaintenanceOrder::query()->firstOrFail();

    expect($order->maintenance_plan_id)->toBe($faellig->getKey());
    expect($order->asset_id)->toBe($faellig->asset_id);
    expect($order->organization_id)->toBe($faellig->organization_id);
    expect($order->status)->toBe(MaintenanceOrderStatus::Open);
});

it('completes an order, stores the protocol, advances the plan and creates a defect ticket', function () {
    $admin = loginAsAdminInFmPanel();

    $plan = MaintenancePlan::factory()->create([
        'interval_value' => 6,
        'interval_unit' => MaintenanceIntervalUnit::Months->value,
        'next_due_at' => now()->toDateString(),
    ]);

    $order = MaintenanceOrder::factory()->create([
        'maintenance_plan_id' => $plan->getKey(),
        'due_at' => now()->toDateString(),
    ]);

    Livewire::test(ListMaintenanceOrders::class)
        ->callAction(TestAction::make('complete')->table($order), data: [
            'notes' => 'Alles geprüft.',
            'defect_found' => true,
            'defect_title' => 'Anschlusskabel beschädigt',
            'defect_description' => 'Isolierung defekt.',
        ])
        ->assertHasNoActionErrors();

    $order = $order->fresh();

    expect($order->status)->toBe(MaintenanceOrderStatus::Completed);
    expect($order->completed_at)->not->toBeNull();
    expect($order->completed_by)->toBe($admin->getKey());

    expect($plan->fresh()->next_due_at->toDateString())->toBe(now()->addMonths(6)->toDateString());

    $ticket = Ticket::query()->firstOrFail();

    expect($ticket->type)->toBe(TicketType::Defect);
    expect($ticket->asset_id)->toBe($order->asset_id);
    expect($ticket->title)->toBe('Anschlusskabel beschädigt');
});

it('only lists plans of the current tenant', function () {
    loginAsAdminInFmPanel();

    $eigenerPlan = MaintenancePlan::factory()->create();

    $fremdeOrganisation = Organization::factory()->create();
    $fremderPlan = inOrganization($fremdeOrganisation, fn (): MaintenancePlan => MaintenancePlan::factory()->create());

    Livewire::test(ListMaintenancePlans::class)
        ->assertCanSeeTableRecords([$eigenerPlan])
        ->assertCanNotSeeTableRecords([$fremderPlan]);
});

it('shows the compliance stats widget with overdue orders', function () {
    loginAsAdminInFmPanel();

    MaintenanceOrder::factory()->create(['due_at' => now()->subDays(3)->toDateString()]);

    Livewire::test(MaintenanceStatsWidget::class)
        ->assertSee('Überfällige Prüfungen/Wartungen')
        ->assertSee('Fällig in 30 Tagen')
        ->assertSee('Aktive Pläne');
});

it('denies maintenance access to the Melder role', function () {
    loginAsAdminInFmPanel();

    $organization = defaultOrganization();

    $melder = User::factory()->create(['email' => 'mieter2@example.com']);
    $melder->organizations()->attach($organization);
    $melder->assignRole(Role::query()->where('name', 'Melder')->where('organization_id', $organization->getKey())->firstOrFail());

    expect($melder->can('viewAny', MaintenancePlan::class))->toBeFalse();
    expect($melder->can('viewAny', MaintenanceOrder::class))->toBeFalse();
});

it('groups the maintenance resources under a shared navigation group', function () {
    loginAsAdminInFmPanel();

    expect(MaintenancePlanResource::getNavigationGroup())->toBe('Wartung & Prüfung');
    expect(MaintenancePlanTemplateResource::getNavigationGroup())->toBe('Wartung & Prüfung');
    expect(MaintenanceOrderResource::getNavigationGroup())->toBe('Wartung & Prüfung');

    $this->get('/fm/demo')
        ->assertSuccessful()
        ->assertSee('Wartung & Prüfung');
});
