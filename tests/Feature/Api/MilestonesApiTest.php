<?php

use App\Models\Milestone;
use App\Models\Organization;
use App\Models\Project;
use Filament\Facades\Filament;

beforeEach(function (): void {
    Filament::setTenant(Organization::factory()->create(), isQuiet: true);
});

it('lists milestones with filters, sorting and includes', function () {
    $project = Project::factory()->create(['name' => 'Umbau Nord']);
    $phase = $project->phases()->create(['name' => 'Planung']);
    $offen = $project->milestones()->create(['name' => 'Freigabe', 'phase_id' => $phase->getKey(), 'due_date' => '2026-02-01', 'sort' => 2]);
    $project->milestones()->create(['name' => 'Abnahme', 'due_date' => '2026-06-01', 'sort' => 1, 'completed_at' => now()]);
    Project::factory()->create()->milestones()->create(['name' => 'Anderes', 'due_date' => '2026-04-01', 'sort' => 3]);

    actingAsApiUser(['projects:read']);

    $this->getJson('/api/v1/milestones')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.name', 'Freigabe')
        ->assertJsonStructure(['data' => [['id', 'project_id', 'phase_id', 'name', 'due_date', 'completed_at', 'sort', 'links' => ['self']]], 'links', 'meta']);

    $this->getJson('/api/v1/milestones?filter[project_id]='.$project->getKey())->assertJsonCount(2, 'data');
    $this->getJson('/api/v1/milestones?filter[phase_id]='.$phase->getKey())->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $offen->getKey());
    $this->getJson('/api/v1/milestones?filter[completed]=true')->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Abnahme');
    $this->getJson('/api/v1/milestones?filter[completed]=false')->assertJsonCount(2, 'data');
    $this->getJson('/api/v1/milestones?filter[due_before]=2026-04-01')->assertJsonCount(2, 'data');
    $this->getJson('/api/v1/milestones?sort=sort')->assertJsonPath('data.0.name', 'Abnahme');
    $this->getJson('/api/v1/milestones?sort=-due_date')->assertJsonPath('data.0.name', 'Abnahme');
    $this->getJson('/api/v1/milestones?sort=name')->assertJsonPath('data.0.name', 'Abnahme');

    $this->getJson('/api/v1/milestones?include=project,phase&filter[phase_id]='.$phase->getKey())
        ->assertOk()
        ->assertJsonPath('data.0.project.name', 'Umbau Nord')
        ->assertJsonPath('data.0.phase.name', 'Planung');
});

it('shows a milestone and hides milestones of other organizations', function () {
    $milestone = Project::factory()->create()->milestones()->create(['name' => 'Freigabe']);
    $fremd = inOrganization(Organization::factory()->create(), fn (): Milestone => Project::factory()->create()->milestones()->create(['name' => 'Fremd']));

    actingAsApiUser(['projects:read']);

    $this->getJson('/api/v1/milestones')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $milestone->getKey());
    $this->getJson('/api/v1/milestones/'.$milestone->getKey().'?include=project')->assertOk()->assertJsonPath('data.project.id', $milestone->project_id);
    $this->getJson('/api/v1/milestones/'.$milestone->getKey().'?include=nope')->assertStatus(400);
    $this->getJson('/api/v1/milestones/'.$fremd->getKey())->assertNotFound();
});

it('creates, updates and deletes milestones with project-bound phases', function () {
    $project = Project::factory()->create();
    $phase = $project->phases()->create(['name' => 'Planung']);
    $fremdePhase = Project::factory()->create()->phases()->create(['name' => 'Anderes Projekt']);
    $fremdesProjekt = inOrganization(Organization::factory()->create(), fn (): Project => Project::factory()->create());

    actingAsApiUser(['projects:write']);

    $this->postJson('/api/v1/milestones', ['project_id' => $fremdesProjekt->getKey(), 'name' => 'Freigabe'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['project_id']);

    $this->postJson('/api/v1/milestones', ['project_id' => $project->getKey(), 'phase_id' => $fremdePhase->getKey(), 'name' => 'Freigabe'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['phase_id']);

    $response = $this->postJson('/api/v1/milestones', ['project_id' => $project->getKey(), 'phase_id' => $phase->getKey(), 'name' => 'Freigabe', 'due_date' => '2026-02-01', 'sort' => 1])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Freigabe')
        ->assertJsonPath('data.due_date', '2026-02-01')
        ->assertJsonPath('data.completed_at', null)
        ->assertJsonPath('data.phase.id', $phase->getKey());

    $milestone = Milestone::query()->findOrFail($response->json('data.id'));

    $this->patchJson('/api/v1/milestones/'.$milestone->getKey(), ['phase_id' => $fremdePhase->getKey()])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['phase_id']);

    $this->patchJson('/api/v1/milestones/'.$milestone->getKey(), ['completed_at' => '2026-02-03 10:00:00', 'phase_id' => null])
        ->assertOk()
        ->assertJsonPath('data.phase_id', null)
        ->assertJsonPath('data.name', 'Freigabe');

    expect($milestone->fresh()->completed_at)->not->toBeNull();

    $this->deleteJson('/api/v1/milestones/'.$milestone->getKey())->assertNoContent();

    expect(Milestone::query()->find($milestone->getKey()))->toBeNull();
});

it('enforces abilities and tenant isolation on writes', function () {
    $milestone = Project::factory()->create()->milestones()->create(['name' => 'Freigabe']);
    $fremd = inOrganization(Organization::factory()->create(), fn (): Milestone => Project::factory()->create()->milestones()->create(['name' => 'Fremd']));

    actingAsApiUser(['projects:read']);

    $this->postJson('/api/v1/milestones', ['project_id' => $milestone->project_id, 'name' => 'X'])->assertForbidden();
    $this->patchJson('/api/v1/milestones/'.$milestone->getKey(), ['name' => 'X'])->assertForbidden();
    $this->deleteJson('/api/v1/milestones/'.$milestone->getKey())->assertForbidden();

    actingAsApiUser(['projects:write']);

    $this->patchJson('/api/v1/milestones/'.$fremd->getKey(), ['name' => 'X'])->assertNotFound();
    $this->deleteJson('/api/v1/milestones/'.$fremd->getKey())->assertNotFound();

    expect($fremd->fresh()->name)->toBe('Fremd');
});
