<?php

use App\Enums\MaintenanceOrderStatus;
use App\Filament\Fm\Resources\MaintenanceOrderResource\Pages\ListMaintenanceOrders;
use App\Models\Asset;
use App\Models\MaintenanceOrder;
use App\Models\MaintenancePlan;
use App\Models\MaintenancePlanTemplate;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

it('copies checklist tasks from template to plan to order', function () {
    loginAsAdminInFmPanel();

    $template = MaintenancePlanTemplate::factory()->create([
        'tasks' => ['Sichtprüfung', 'Funktionstest', 'Protokoll erstellen'],
    ]);

    $asset = Asset::factory()->create(['asset_type_id' => $template->asset_type_id]);

    $plan = MaintenancePlan::query()
        ->where('maintenance_plan_template_id', $template->getKey())
        ->where('asset_id', $asset->getKey())
        ->firstOrFail();

    expect($plan->tasks)->toBe(['Sichtprüfung', 'Funktionstest', 'Protokoll erstellen']);

    $order = MaintenanceOrder::firstOrCreateForPlanDueDate($plan);

    expect($order->tasks)->toBe(['Sichtprüfung', 'Funktionstest', 'Protokoll erstellen']);
});

it('properties completed checklist items when finishing an order', function () {
    loginAsAdminInFmPanel();

    $plan = MaintenancePlan::factory()->create(['tasks' => ['Sichtprüfung', 'Funktionstest']]);
    $order = MaintenanceOrder::firstOrCreateForPlanDueDate($plan);

    Livewire::test(ListMaintenanceOrders::class)
        ->callAction(TestAction::make('complete')->table($order), data: [
            'completed_tasks' => ['Sichtprüfung'],
        ])
        ->assertHasNoActionErrors();

    $order->refresh();

    expect($order->status)->toBe(MaintenanceOrderStatus::Completed);
    expect($order->completed_tasks)->toBe(['Sichtprüfung']);
});
