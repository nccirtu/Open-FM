<?php

use App\Models\Organization;
use App\Models\Project;
use App\Models\Property;
use App\Models\User;
use Filament\Facades\Filament;

beforeEach(function (): void {
    Filament::setTenant(Organization::factory()->create(), isQuiet: true);
});

it('lists projects with filters, sorting and includes', function () {
    $property = Property::factory()->create(['property_number' => '3000']);
    $manager = User::factory()->create();
    $manager->organizations()->attach(Filament::getTenant());
    $treffer = Project::factory()->create(['code' => 'P-0001', 'name' => 'Umbau Standort Nord', 'status' => 'active', 'type' => 'remodel', 'health' => 'red', 'property_id' => $property->getKey(), 'manager_id' => $manager->getKey(), 'start_date' => '2026-01-10', 'end_date' => '2026-03-31']);
    Project::factory()->create(['code' => 'P-0002', 'name' => 'Neubau Lager', 'status' => 'done', 'type' => 'new_build', 'health' => 'green', 'start_date' => '2025-01-01', 'end_date' => '2025-12-31']);
    $treffer->phases()->create(['name' => 'Planung', 'sort' => 1]);

    actingAsApiUser(['projects:read']);

    $this->getJson('/api/v1/projects')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure(['data' => [['id', 'code', 'name', 'status', 'health', 'links' => ['self']]], 'links', 'meta']);

    $this->getJson('/api/v1/projects?filter[search]=nord')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $treffer->getKey());
    $this->getJson('/api/v1/projects?filter[search]=P-0002')->assertJsonCount(1, 'data')->assertJsonPath('data.0.code', 'P-0002');
    $this->getJson('/api/v1/projects?filter[status]=active')->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/projects?filter[type]=new_build')->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/projects?filter[health]=red')->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/projects?filter[property_id]='.$property->getKey())->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/projects?filter[manager_id]='.$manager->getKey())->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/projects?filter[start_from]=2026-01-01')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $treffer->getKey());
    $this->getJson('/api/v1/projects?filter[end_before]=2025-12-31')->assertJsonCount(1, 'data')->assertJsonPath('data.0.code', 'P-0002');
    $this->getJson('/api/v1/projects?sort=name')->assertJsonPath('data.0.name', 'Neubau Lager');
    $this->getJson('/api/v1/projects?sort=-start_date')->assertJsonPath('data.0.code', 'P-0001');

    $this->getJson('/api/v1/projects?include=property,manager,phases&filter[code]=x')->assertStatus(400);

    $this->getJson('/api/v1/projects?include=property,manager,phases&filter[search]=nord')
        ->assertOk()
        ->assertJsonPath('data.0.property.property_number', '3000')
        ->assertJsonPath('data.0.manager.id', $manager->getKey())
        ->assertJsonPath('data.0.phases.0.name', 'Planung');
});

it('shows a single project with requested relations', function () {
    $project = Project::factory()->create();
    $project->milestones()->create(['name' => 'Rohbau fertig', 'due_date' => '2026-05-01']);
    $project->budgetPositions()->create(['name' => 'Elektro', 'budget' => 1000]);

    actingAsApiUser(['projects:read']);

    $this->getJson('/api/v1/projects/'.$project->getKey().'?include=milestones,budgetPositions')
        ->assertOk()
        ->assertJsonPath('data.id', $project->getKey())
        ->assertJsonPath('data.milestones.0.name', 'Rohbau fertig')
        ->assertJsonPath('data.budget_positions.0.name', 'Elektro');

    $this->getJson('/api/v1/projects/'.$project->getKey().'?include=nope')->assertStatus(400);
});

it('hides projects of other organizations', function () {
    $project = Project::factory()->create();
    $fremd = inOrganization(Organization::factory()->create(), fn (): Project => Project::factory()->create());

    actingAsApiUser(['projects:read']);

    $this->getJson('/api/v1/projects')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $project->getKey());
    $this->getJson('/api/v1/projects/'.$fremd->getKey())->assertNotFound();
    $this->getJson('/api/v1/projects?filter[search]=')->assertOk();
});

it('creates, updates and deletes projects with tenant-scoped validation', function () {
    $property = Property::factory()->create();
    $manager = User::factory()->create();
    $manager->organizations()->attach(Filament::getTenant());
    $fremdesObjekt = inOrganization(Organization::factory()->create(), fn (): Property => Property::factory()->create());
    $fremderUser = User::factory()->create();

    $user = actingAsApiUser(['projects:write']);

    $this->postJson('/api/v1/projects', ['name' => 'Umbau', 'property_id' => $fremdesObjekt->getKey(), 'manager_id' => $fremderUser->getKey()])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['property_id', 'manager_id']);

    $this->postJson('/api/v1/projects', ['name' => 'Umbau', 'start_date' => '2026-02-01', 'end_date' => '2026-01-01', 'status' => 'unknown'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['end_date', 'status']);

    $response = $this->postJson('/api/v1/projects', [
        'name' => 'Umbau Standort Süd',
        'code' => 'P-0100',
        'property_id' => $property->getKey(),
        'manager_id' => $manager->getKey(),
        'type' => 'remodel',
        'status' => 'open',
        'health' => 'yellow',
        'start_date' => '2026-02-01',
        'end_date' => '2026-06-30',
        'budget' => 250000,
    ])->assertCreated()
        ->assertJsonPath('data.name', 'Umbau Standort Süd')
        ->assertJsonPath('data.type', 'remodel')
        ->assertJsonPath('data.health', 'yellow')
        ->assertJsonPath('data.start_date', '2026-02-01')
        ->assertJsonPath('data.property.id', $property->getKey())
        ->assertJsonPath('data.manager.id', $manager->getKey());

    $project = Project::query()->findOrFail($response->json('data.id'));

    expect($project->organization_id)->toBe(Filament::getTenant()->getKey())
        ->and($project->created_by)->toBe($user->getKey());

    $this->patchJson('/api/v1/projects/'.$project->getKey(), ['manager_id' => $fremderUser->getKey()])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['manager_id']);

    $this->patchJson('/api/v1/projects/'.$project->getKey(), ['status' => 'active', 'budget' => 300000, 'manager_id' => null])
        ->assertOk()
        ->assertJsonPath('data.status', 'active')
        ->assertJsonPath('data.manager_id', null)
        ->assertJsonPath('data.name', 'Umbau Standort Süd');

    $project->tasks()->create(['title' => 'Aufgabe']);

    $this->deleteJson('/api/v1/projects/'.$project->getKey())->assertNoContent();

    expect(Project::query()->find($project->getKey()))->toBeNull()
        ->and($project->tasks()->count())->toBe(0);
});

it('refuses writes and cross-tenant updates', function () {
    $project = Project::factory()->create();
    $fremd = inOrganization(Organization::factory()->create(), fn (): Project => Project::factory()->create());

    actingAsApiUser(['projects:read']);

    $this->postJson('/api/v1/projects', ['name' => 'Neu'])->assertForbidden();
    $this->patchJson('/api/v1/projects/'.$project->getKey(), ['name' => 'Neu'])->assertForbidden();
    $this->deleteJson('/api/v1/projects/'.$project->getKey())->assertForbidden();

    actingAsApiUser(['projects:write']);

    $this->patchJson('/api/v1/projects/'.$fremd->getKey(), ['name' => 'Neu'])->assertNotFound();
    $this->deleteJson('/api/v1/projects/'.$fremd->getKey())->assertNotFound();
    $this->getJson('/api/v1/projects/'.$project->getKey())->assertOk();
});
