<?php

use App\Models\BudgetPosition;
use App\Models\ClassificationNode;
use App\Models\Organization;
use App\Models\Project;
use Filament\Facades\Filament;

beforeEach(function (): void {
    Filament::setTenant(Organization::factory()->create(), isQuiet: true);
});

it('lists budget positions with filters, sorting and includes', function () {
    $project = Project::factory()->create(['name' => 'Umbau Nord']);
    $costCenter = ClassificationNode::query()->create(['code' => '440', 'name' => 'Elektro']);
    $elektro = $project->budgetPositions()->create(['name' => 'Elektroinstallation', 'cost_group' => '440', 'cost_center_id' => $costCenter->getKey(), 'budget' => 40000, 'sort' => 2]);
    $project->budgetPositions()->create(['name' => 'Bodenbelag', 'cost_group' => '350', 'budget' => 20000, 'sort' => 1]);
    Project::factory()->create()->budgetPositions()->create(['name' => 'Anderes', 'budget' => 5000, 'sort' => 3]);
    $elektro->costEntries()->create(['project_id' => $project->getKey(), 'amount' => 1000, 'booked_on' => '2026-01-01']);

    actingAsApiUser(['projects:read']);

    $this->getJson('/api/v1/budget-positions')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.name', 'Bodenbelag')
        ->assertJsonStructure(['data' => [['id', 'project_id', 'name', 'cost_group', 'cost_center_id', 'budget', 'sort', 'links' => ['self']]], 'links', 'meta']);

    $this->getJson('/api/v1/budget-positions?filter[project_id]='.$project->getKey())->assertJsonCount(2, 'data');
    $this->getJson('/api/v1/budget-positions?filter[cost_group]=44')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $elektro->getKey());
    $this->getJson('/api/v1/budget-positions?filter[cost_center_id]='.$costCenter->getKey())->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/budget-positions?sort=-budget')->assertJsonPath('data.0.name', 'Elektroinstallation');
    $this->getJson('/api/v1/budget-positions?sort=name')->assertJsonPath('data.0.name', 'Anderes');

    $this->getJson('/api/v1/budget-positions?include=project,costCenter,costEntries&filter[cost_group]=440')
        ->assertOk()
        ->assertJsonPath('data.0.project.name', 'Umbau Nord')
        ->assertJsonPath('data.0.cost_center.code', '440')
        ->assertJsonPath('data.0.cost_entries.0.amount', '1000.00');
});

it('shows a budget position and hides positions of other organizations', function () {
    $position = Project::factory()->create()->budgetPositions()->create(['name' => 'Elektro', 'budget' => 100]);
    $fremd = inOrganization(Organization::factory()->create(), fn (): BudgetPosition => Project::factory()->create()->budgetPositions()->create(['name' => 'Fremd', 'budget' => 1]));

    actingAsApiUser(['projects:read']);

    $this->getJson('/api/v1/budget-positions')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $position->getKey());
    $this->getJson('/api/v1/budget-positions/'.$position->getKey().'?include=project')->assertOk()->assertJsonPath('data.project.id', $position->project_id);
    $this->getJson('/api/v1/budget-positions/'.$position->getKey().'?include=nope')->assertStatus(400);
    $this->getJson('/api/v1/budget-positions/'.$fremd->getKey())->assertNotFound();
});

it('creates, updates and deletes budget positions with tenant-scoped validation', function () {
    $project = Project::factory()->create();
    $costCenter = ClassificationNode::query()->create(['code' => '440', 'name' => 'Elektro']);
    $fremdesProjekt = inOrganization(Organization::factory()->create(), fn (): Project => Project::factory()->create());
    $fremdeKostenstelle = inOrganization(Organization::factory()->create(), fn (): ClassificationNode => ClassificationNode::query()->create(['code' => '300', 'name' => 'Fremd']));

    actingAsApiUser(['projects:write']);

    $this->postJson('/api/v1/budget-positions', ['project_id' => $fremdesProjekt->getKey(), 'name' => 'Elektro', 'cost_center_id' => $fremdeKostenstelle->getKey()])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['project_id', 'cost_center_id']);

    $this->postJson('/api/v1/budget-positions', ['project_id' => $project->getKey(), 'name' => 'Elektro', 'budget' => -5])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['budget']);

    $response = $this->postJson('/api/v1/budget-positions', ['project_id' => $project->getKey(), 'name' => 'Elektro', 'cost_group' => '440', 'cost_center_id' => $costCenter->getKey(), 'budget' => 40000, 'sort' => 1])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Elektro')
        ->assertJsonPath('data.budget', '40000.00')
        ->assertJsonPath('data.cost_center.id', $costCenter->getKey())
        ->assertJsonPath('data.project.id', $project->getKey());

    $position = BudgetPosition::query()->findOrFail($response->json('data.id'));

    $this->patchJson('/api/v1/budget-positions/'.$position->getKey(), ['cost_center_id' => $fremdeKostenstelle->getKey()])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['cost_center_id']);

    $this->patchJson('/api/v1/budget-positions/'.$position->getKey(), ['budget' => 45000, 'cost_center_id' => null])
        ->assertOk()
        ->assertJsonPath('data.budget', '45000.00')
        ->assertJsonPath('data.cost_center_id', null)
        ->assertJsonPath('data.name', 'Elektro');

    $entry = $position->costEntries()->create(['project_id' => $project->getKey(), 'amount' => 100, 'booked_on' => '2026-01-01']);

    $this->deleteJson('/api/v1/budget-positions/'.$position->getKey())->assertStatus(409);

    $entry->delete();

    $this->deleteJson('/api/v1/budget-positions/'.$position->getKey())->assertNoContent();

    expect(BudgetPosition::query()->find($position->getKey()))->toBeNull();
});

it('enforces abilities and tenant isolation on writes', function () {
    $position = Project::factory()->create()->budgetPositions()->create(['name' => 'Elektro', 'budget' => 100]);
    $fremd = inOrganization(Organization::factory()->create(), fn (): BudgetPosition => Project::factory()->create()->budgetPositions()->create(['name' => 'Fremd', 'budget' => 1]));

    actingAsApiUser(['projects:read']);

    $this->postJson('/api/v1/budget-positions', ['project_id' => $position->project_id, 'name' => 'X'])->assertForbidden();
    $this->patchJson('/api/v1/budget-positions/'.$position->getKey(), ['name' => 'X'])->assertForbidden();
    $this->deleteJson('/api/v1/budget-positions/'.$position->getKey())->assertForbidden();

    actingAsApiUser(['projects:write']);

    $this->patchJson('/api/v1/budget-positions/'.$fremd->getKey(), ['name' => 'X'])->assertNotFound();
    $this->deleteJson('/api/v1/budget-positions/'.$fremd->getKey())->assertNotFound();

    expect($fremd->fresh()->name)->toBe('Fremd');
});
