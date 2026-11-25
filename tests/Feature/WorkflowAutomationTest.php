<?php

use App\Filament\Admin\Resources\WorkflowResource\Pages\CreateWorkflow;
use App\Filament\Admin\Resources\WorkflowResource\Pages\EditWorkflow;
use App\Flow\Actions\CreateMaintenanceOrderAction;
use App\Flow\Actions\CreateTicketAction;
use App\Flow\Conditions\SlaBreachedCondition;
use App\Flow\Triggers\RecordCreated;
use App\Models\Asset;
use App\Models\MaintenanceOrder;
use App\Models\MaintenancePlan;
use App\Models\Organization;
use App\Models\Ticket;
use Packstub\Flow\Enums\RunStatus;
use Packstub\Flow\Facades\Flow;
use Packstub\Flow\Models\Workflow;
use Packstub\Flow\Nodes\Actions\WriteLog;
use Packstub\Flow\Nodes\Triggers\Manual;

it('shows the workflow pages in the admin panel', function () {
    loginAsSuperAdmin();

    $this->get('/admin/workflows')->assertOk();
    $this->get('/admin/workflow-secrets')->assertOk();
    $this->get('/admin/workflow-runs')->assertOk();
    $this->get('/admin/workflow-approvals')->assertOk();
});

it('runs a manually triggered workflow and records the run', function () {
    loginAsAdmin();

    $workflow = Workflow::query()->create([
        'name' => 'Log-Testlauf',
        'is_active' => true,
        'definition' => [
            'nodes' => [
                ['id' => 't1', 'type' => 'trigger', 'data' => ['identifier' => Manual::class, 'config' => []]],
                ['id' => 'a1', 'type' => 'action', 'data' => ['identifier' => WriteLog::class, 'config' => ['level' => 'info', 'message' => 'Antwort: {{ answer }}']]],
            ],
            'edges' => [
                ['id' => 'e1', 'source' => 't1', 'target' => 'a1'],
            ],
        ],
    ]);

    $run = Flow::run($workflow, ['answer' => 42]);

    expect($run)->not->toBeNull();
    expect($run->refresh()->status)->toBe(RunStatus::Success);
    expect($run->steps()->count())->toBeGreaterThanOrEqual(1);
});

it('assigns a workflow to a tenant via the admin form and scopes runs to it', function () {
    loginAsSuperAdmin();

    Filament\Facades\Filament::setCurrentPanel('admin');
    Filament\Facades\Filament::getPanel('admin')->boot();

    $fremdeOrganisation = Organization::factory()->create();

    Livewire\Livewire::test(CreateWorkflow::class)
        ->fillForm([
            'name' => 'Nur fremder Mandant',
            'tenant_id' => $fremdeOrganisation->getKey(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $workflow = Workflow::query()->where('name', 'Nur fremder Mandant')->firstOrFail();

    expect($workflow->tenant_id)->toBe((string) $fremdeOrganisation->getKey());
    expect($workflow->tenant_type)->toBe((new Organization)->getMorphClass());

    $workflow->update([
        'is_active' => true,
        'definition' => [
            'nodes' => [
                ['id' => 't1', 'type' => 'trigger', 'data' => ['identifier' => RecordCreated::class, 'config' => ['model_class' => Ticket::class]]],
                ['id' => 'a1', 'type' => 'action', 'data' => ['identifier' => WriteLog::class, 'config' => ['level' => 'info', 'message' => 'x']]],
            ],
            'edges' => [['id' => 'e1', 'source' => 't1', 'target' => 'a1']],
        ],
    ]);

    Ticket::factory()->create();

    expect($workflow->runs()->count())->toBe(0);
});

it('changes the tenant in the editor settings and keeps it when the canvas is saved', function () {
    loginAsSuperAdmin();

    Filament\Facades\Filament::setCurrentPanel('admin');
    Filament\Facades\Filament::getPanel('admin')->boot();

    $organisation = Organization::factory()->create();
    $workflow = Workflow::query()->create([
        'name' => 'Mandantenwechsel',
        'is_active' => false,
        'definition' => ['nodes' => [], 'edges' => []],
    ]);

    Livewire\Livewire::test(EditWorkflow::class, ['record' => $workflow->getKey()])
        ->callAction('settings', data: ['tenant_id' => $organisation->getKey()])
        ->assertHasNoActionErrors();

    expect($workflow->refresh()->tenant_id)->toBe((string) $organisation->getKey())
        ->and($workflow->tenant_type)->toBe((new Organization)->getMorphClass());

    Livewire\Livewire::test(EditWorkflow::class, ['record' => $workflow->getKey()])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($workflow->refresh()->tenant_id)->toBe((string) $organisation->getKey());

    Livewire\Livewire::test(EditWorkflow::class, ['record' => $workflow->getKey()])
        ->callAction('settings', data: ['tenant_id' => null])
        ->assertHasNoActionErrors();

    expect($workflow->refresh()->tenant_id)->toBeNull()
        ->and($workflow->tenant_type)->toBeNull();
});

it('creates tickets and maintenance orders via custom nodes and checks sla breaches', function () {
    loginAsAdminInFmPanel();

    $asset = Asset::factory()->create(['name' => 'RLT-Anlage']);
    MaintenancePlan::factory()->create(['asset_id' => $asset->getKey(), 'is_active' => true]);

    $workflow = Workflow::query()->create([
        'name' => 'Eigene Knoten',
        'is_active' => true,
        'definition' => [
            'nodes' => [
                ['id' => 't1', 'type' => 'trigger', 'data' => ['identifier' => Manual::class, 'config' => []]],
                ['id' => 'a1', 'type' => 'action', 'data' => ['identifier' => CreateTicketAction::class, 'config' => ['title' => 'Auffälligkeit an {{ model.name }}', 'priority' => 'p2', 'category' => 'hvac']]],
                ['id' => 'a2', 'type' => 'action', 'data' => ['identifier' => CreateMaintenanceOrderAction::class, 'config' => ['due_in_days' => 3, 'notes' => 'Prüfen: {{ model.name }}']]],
            ],
            'edges' => [
                ['id' => 'e1', 'source' => 't1', 'target' => 'a1'],
                ['id' => 'e2', 'source' => 'a1', 'target' => 'a2'],
            ],
        ],
    ]);

    $run = Flow::run($workflow, ['model' => $asset]);

    expect($run->refresh()->status)->toBe(RunStatus::Success);

    $ticket = Ticket::query()->where('title', 'Auffälligkeit an RLT-Anlage')->firstOrFail();
    expect($ticket->asset_id)->toBe($asset->getKey());
    expect($ticket->property_id)->toBe($asset->property_id);

    $order = MaintenanceOrder::query()->where('asset_id', $asset->getKey())->firstOrFail();
    expect($order->notes)->toBe('Prüfen: RLT-Anlage');

    $bedingung = new SlaBreachedCondition;
    expect($bedingung->evaluate([], ['model' => $ticket]))->toBeFalse();

    $ticket->forceFill(['resolve_due_at' => now()->subHour()])->save();
    expect($bedingung->evaluate([], ['model' => $ticket->refresh()]))->toBeTrue();
});

it('starts a workflow when a ticket is created', function () {
    loginAsAdminInFmPanel();

    $workflow = Workflow::query()->create([
        'name' => 'Bei neuer Störung',
        'is_active' => true,
        'definition' => [
            'nodes' => [
                ['id' => 't1', 'type' => 'trigger', 'data' => ['identifier' => RecordCreated::class, 'config' => ['model_class' => Ticket::class]]],
                ['id' => 'a1', 'type' => 'action', 'data' => ['identifier' => WriteLog::class, 'config' => ['level' => 'info', 'message' => 'Neue Störung: {{ model.title }}']]],
            ],
            'edges' => [
                ['id' => 'e1', 'source' => 't1', 'target' => 'a1'],
            ],
        ],
    ]);

    Ticket::factory()->create(['title' => 'Aufzug defekt']);

    expect($workflow->runs()->count())->toBe(1);
    expect($workflow->runs()->first()->status)->toBe(RunStatus::Success);
});
