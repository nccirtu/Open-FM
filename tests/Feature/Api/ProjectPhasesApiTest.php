<?php

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectPhase;
use Filament\Facades\Filament;

beforeEach(function (): void {
    Filament::setTenant(Organization::factory()->create(), isQuiet: true);
});

it('lists project phases with filters, sorting and includes', function () {
    $project = Project::factory()->create(['name' => 'Umbau Nord']);
    $other = Project::factory()->create();
    $planung = $project->phases()->create(['name' => 'Planung', 'sort' => 2, 'status' => 'active', 'start_date' => '2026-01-01']);
    $project->phases()->create(['name' => 'Ausführung', 'sort' => 1, 'status' => 'planned', 'start_date' => '2026-03-01']);
    $other->phases()->create(['name' => 'Abnahme', 'sort' => 3, 'status' => 'done']);
    $planung->milestones()->create(['project_id' => $project->getKey(), 'name' => 'Freigabe']);

    actingAsApiUser(['projects:read']);

    $this->getJson('/api/v1/project-phases')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.name', 'Ausführung')
        ->assertJsonStructure(['data' => [['id', 'project_id', 'name', 'sort', 'status', 'links' => ['self']]], 'links', 'meta']);

    $this->getJson('/api/v1/project-phases?filter[project_id]='.$project->getKey())->assertJsonCount(2, 'data');
    $this->getJson('/api/v1/project-phases?filter[status]=done')->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Abnahme');
    $this->getJson('/api/v1/project-phases?sort=-start_date')->assertJsonPath('data.0.name', 'Ausführung');
    $this->getJson('/api/v1/project-phases?sort=name')->assertJsonPath('data.0.name', 'Abnahme');

    $this->getJson('/api/v1/project-phases?include=project,milestones&filter[project_id]='.$project->getKey().'&sort=-sort')
        ->assertOk()
        ->assertJsonPath('data.0.project.name', 'Umbau Nord')
        ->assertJsonPath('data.0.milestones.0.name', 'Freigabe');
});

it('shows a phase and hides phases of other organizations', function () {
    $phase = Project::factory()->create()->phases()->create(['name' => 'Planung']);
    $fremd = inOrganization(Organization::factory()->create(), fn (): ProjectPhase => Project::factory()->create()->phases()->create(['name' => 'Fremd']));

    actingAsApiUser(['projects:read']);

    $this->getJson('/api/v1/project-phases')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $phase->getKey());
    $this->getJson('/api/v1/project-phases/'.$phase->getKey().'?include=project')->assertOk()->assertJsonPath('data.project.id', $phase->project_id);
    $this->getJson('/api/v1/project-phases/'.$phase->getKey().'?include=nope')->assertStatus(400);
    $this->getJson('/api/v1/project-phases/'.$fremd->getKey())->assertNotFound();
});

it('creates, updates and deletes phases with tenant-scoped validation', function () {
    $project = Project::factory()->create();
    $fremdesProjekt = inOrganization(Organization::factory()->create(), fn (): Project => Project::factory()->create());

    actingAsApiUser(['projects:write']);

    $this->postJson('/api/v1/project-phases', ['project_id' => $fremdesProjekt->getKey(), 'name' => 'Planung'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['project_id']);

    $this->postJson('/api/v1/project-phases', ['project_id' => $project->getKey()])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);

    $response = $this->postJson('/api/v1/project-phases', ['project_id' => $project->getKey(), 'name' => 'Planung', 'sort' => 1, 'status' => 'active', 'start_date' => '2026-01-01', 'end_date' => '2026-02-28'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Planung')
        ->assertJsonPath('data.status', 'active')
        ->assertJsonPath('data.end_date', '2026-02-28')
        ->assertJsonPath('data.project.id', $project->getKey());

    $phase = ProjectPhase::query()->findOrFail($response->json('data.id'));

    $this->patchJson('/api/v1/project-phases/'.$phase->getKey(), ['project_id' => $fremdesProjekt->getKey()])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['project_id']);

    $this->patchJson('/api/v1/project-phases/'.$phase->getKey(), ['status' => 'done', 'sort' => 5])
        ->assertOk()
        ->assertJsonPath('data.status', 'done')
        ->assertJsonPath('data.sort', 5)
        ->assertJsonPath('data.name', 'Planung');

    $milestone = $project->milestones()->create(['name' => 'Freigabe', 'phase_id' => $phase->getKey()]);

    $this->deleteJson('/api/v1/project-phases/'.$phase->getKey())->assertNoContent();

    expect(ProjectPhase::query()->find($phase->getKey()))->toBeNull()
        ->and($milestone->fresh()->phase_id)->toBeNull();
});

it('enforces abilities and tenant isolation on writes', function () {
    $phase = Project::factory()->create()->phases()->create(['name' => 'Planung']);
    $fremd = inOrganization(Organization::factory()->create(), fn (): ProjectPhase => Project::factory()->create()->phases()->create(['name' => 'Fremd']));

    actingAsApiUser(['projects:read']);

    $this->postJson('/api/v1/project-phases', ['project_id' => $phase->project_id, 'name' => 'X'])->assertForbidden();
    $this->patchJson('/api/v1/project-phases/'.$phase->getKey(), ['name' => 'X'])->assertForbidden();
    $this->deleteJson('/api/v1/project-phases/'.$phase->getKey())->assertForbidden();

    actingAsApiUser(['projects:write']);

    $this->patchJson('/api/v1/project-phases/'.$fremd->getKey(), ['name' => 'X'])->assertNotFound();
    $this->deleteJson('/api/v1/project-phases/'.$fremd->getKey())->assertNotFound();

    expect($fremd->fresh()->name)->toBe('Fremd');
});
