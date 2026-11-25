<?php

use App\Models\ChangeOrder;
use App\Models\Organization;
use App\Models\Project;
use Filament\Facades\Filament;

beforeEach(function (): void {
    Filament::setTenant(Organization::factory()->create(), isQuiet: true);
});

it('lists change orders with filters, sorting and includes', function () {
    $project = Project::factory()->create(['name' => 'Umbau Nord']);
    $position = $project->budgetPositions()->create(['name' => 'Elektro', 'budget' => 1000]);
    $treffer = $project->changeOrders()->create(['budget_position_id' => $position->getKey(), 'title' => 'Mehraufwand Kabelwege', 'amount' => 8000, 'status' => 'approved', 'requested_on' => '2026-02-01']);
    $project->changeOrders()->create(['title' => 'Zusätzliche Steckdosen', 'amount' => 500, 'status' => 'pending', 'requested_on' => '2026-03-01']);
    Project::factory()->create()->changeOrders()->create(['title' => 'Anderes', 'amount' => 100, 'status' => 'rejected', 'requested_on' => '2026-01-01']);

    actingAsApiUser(['projects:read']);

    $this->getJson('/api/v1/change-orders')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.title', 'Zusätzliche Steckdosen')
        ->assertJsonStructure(['data' => [['id', 'project_id', 'budget_position_id', 'title', 'amount', 'status', 'requested_on', 'decided_at', 'decided_by', 'links' => ['self']]], 'links', 'meta']);

    $this->getJson('/api/v1/change-orders?filter[project_id]='.$project->getKey())->assertJsonCount(2, 'data');
    $this->getJson('/api/v1/change-orders?filter[status]=approved')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $treffer->getKey());
    $this->getJson('/api/v1/change-orders?filter[status]=approved,rejected')->assertJsonCount(2, 'data');
    $this->getJson('/api/v1/change-orders?filter[budget_position_id]='.$position->getKey())->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/change-orders?sort=-amount')->assertJsonPath('data.0.id', $treffer->getKey());
    $this->getJson('/api/v1/change-orders?sort=requested_on')->assertJsonPath('data.0.title', 'Anderes');
    $this->getJson('/api/v1/change-orders?sort=status')->assertJsonPath('data.0.status', 'approved');

    $this->getJson('/api/v1/change-orders?include=project,budgetPosition&filter[status]=approved')
        ->assertOk()
        ->assertJsonPath('data.0.project.name', 'Umbau Nord')
        ->assertJsonPath('data.0.budget_position.name', 'Elektro');
});

it('shows a change order and hides orders of other organizations', function () {
    $changeOrder = Project::factory()->create()->changeOrders()->create(['title' => 'Nachtrag', 'amount' => 10]);
    $fremd = inOrganization(Organization::factory()->create(), fn (): ChangeOrder => Project::factory()->create()->changeOrders()->create(['title' => 'Fremd', 'amount' => 1]));

    actingAsApiUser(['projects:read']);

    $this->getJson('/api/v1/change-orders')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $changeOrder->getKey());
    $this->getJson('/api/v1/change-orders/'.$changeOrder->getKey().'?include=project,decider')
        ->assertOk()
        ->assertJsonPath('data.project.id', $changeOrder->project_id)
        ->assertJsonPath('data.decider', null);
    $this->getJson('/api/v1/change-orders/'.$changeOrder->getKey().'?include=nope')->assertStatus(400);
    $this->getJson('/api/v1/change-orders/'.$fremd->getKey())->assertNotFound();
});

it('creates, decides, updates and deletes change orders', function () {
    $project = Project::factory()->create(['budget' => 10000]);
    $position = $project->budgetPositions()->create(['name' => 'Elektro', 'budget' => 1000]);
    $fremdePosition = Project::factory()->create()->budgetPositions()->create(['name' => 'Anderes', 'budget' => 1]);
    $fremdesProjekt = inOrganization(Organization::factory()->create(), fn (): Project => Project::factory()->create());

    $user = actingAsApiUser(['projects:write']);

    $this->postJson('/api/v1/change-orders', ['project_id' => $fremdesProjekt->getKey(), 'title' => 'X', 'amount' => 1])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['project_id']);

    $this->postJson('/api/v1/change-orders', ['project_id' => $project->getKey(), 'budget_position_id' => $fremdePosition->getKey(), 'status' => 'nope'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['budget_position_id', 'title', 'amount', 'status']);

    $response = $this->postJson('/api/v1/change-orders', ['project_id' => $project->getKey(), 'budget_position_id' => $position->getKey(), 'title' => 'Mehraufwand', 'reason' => 'Zusätzliche Kabelwege', 'amount' => 800, 'requested_on' => '2026-02-01'])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Mehraufwand')
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.amount', '800.00')
        ->assertJsonPath('data.decided_at', null)
        ->assertJsonPath('data.budget_position.id', $position->getKey());

    $changeOrder = ChangeOrder::query()->findOrFail($response->json('data.id'));

    $this->patchJson('/api/v1/change-orders/'.$changeOrder->getKey(), ['budget_position_id' => $fremdePosition->getKey()])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['budget_position_id']);

    $this->patchJson('/api/v1/change-orders/'.$changeOrder->getKey(), ['status' => 'approved', 'amount' => 900])
        ->assertOk()
        ->assertJsonPath('data.status', 'approved')
        ->assertJsonPath('data.amount', '900.00')
        ->assertJsonPath('data.decided_by', $user->getKey())
        ->assertJsonPath('data.decider.id', $user->getKey());

    expect($changeOrder->fresh()->decided_at)->not->toBeNull()
        ->and($position->plannedBudget())->toBe(1900.0);

    $this->patchJson('/api/v1/change-orders/'.$changeOrder->getKey(), ['status' => 'pending'])
        ->assertOk()
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.decided_at', null)
        ->assertJsonPath('data.decided_by', null);

    $direkt = $this->postJson('/api/v1/change-orders', ['project_id' => $project->getKey(), 'title' => 'Abgelehnt', 'amount' => 5, 'status' => 'rejected'])
        ->assertCreated()
        ->assertJsonPath('data.status', 'rejected')
        ->assertJsonPath('data.decided_by', $user->getKey());

    expect($direkt->json('data.decided_at'))->not->toBeNull();

    $this->deleteJson('/api/v1/change-orders/'.$changeOrder->getKey())->assertNoContent();

    expect(ChangeOrder::query()->find($changeOrder->getKey()))->toBeNull();
});

it('enforces abilities and tenant isolation on writes', function () {
    $changeOrder = Project::factory()->create()->changeOrders()->create(['title' => 'Nachtrag', 'amount' => 10]);
    $fremd = inOrganization(Organization::factory()->create(), fn (): ChangeOrder => Project::factory()->create()->changeOrders()->create(['title' => 'Fremd', 'amount' => 1]));

    actingAsApiUser(['projects:read']);

    $this->postJson('/api/v1/change-orders', ['project_id' => $changeOrder->project_id, 'title' => 'X', 'amount' => 1])->assertForbidden();
    $this->patchJson('/api/v1/change-orders/'.$changeOrder->getKey(), ['status' => 'approved'])->assertForbidden();
    $this->deleteJson('/api/v1/change-orders/'.$changeOrder->getKey())->assertForbidden();

    actingAsApiUser(['projects:write']);

    $this->patchJson('/api/v1/change-orders/'.$fremd->getKey(), ['status' => 'approved'])->assertNotFound();
    $this->deleteJson('/api/v1/change-orders/'.$fremd->getKey())->assertNotFound();

    expect($fremd->fresh()->status->value)->toBe('pending');
});
