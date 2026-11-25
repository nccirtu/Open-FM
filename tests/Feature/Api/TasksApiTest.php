<?php

use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Filament\Facades\Filament;

beforeEach(function (): void {
    Filament::setTenant(Organization::factory()->create(), isQuiet: true);
});

it('lists tasks with filters, sorting and includes', function () {
    $project = Project::factory()->create(['name' => 'Umbau Nord']);
    $phase = $project->phases()->create(['name' => 'Ausführung']);
    $milestone = $project->milestones()->create(['name' => 'Rohbau']);
    $assignee = User::factory()->create();
    $assignee->organizations()->attach(Filament::getTenant());
    $blocker = Task::factory()->create(['project_id' => $project->getKey(), 'title' => 'Bestellung Kabel', 'status' => 'done', 'priority' => 'low', 'due_date' => '2026-01-10', 'position' => 3]);
    $treffer = Task::factory()->create(['project_id' => $project->getKey(), 'phase_id' => $phase->getKey(), 'milestone_id' => $milestone->getKey(), 'assignee_id' => $assignee->getKey(), 'blocked_by_task_id' => $blocker->getKey(), 'title' => 'Kabelwege verlegen', 'description' => 'Elektro EG', 'status' => 'in_progress', 'priority' => 'urgent', 'trade' => 'Elektro', 'due_date' => now()->subDays(2)->toDateString(), 'position' => 1]);
    Task::factory()->create(['title' => 'Andere Aufgabe', 'status' => 'todo', 'priority' => 'normal', 'due_date' => now()->addDays(30)->toDateString(), 'position' => 2]);

    actingAsApiUser(['projects:read']);

    $this->getJson('/api/v1/tasks')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure(['data' => [['id', 'project_id', 'title', 'status', 'priority', 'due_date', 'checklist', 'links' => ['self']]], 'links', 'meta']);

    $this->getJson('/api/v1/tasks?filter[search]=elektro eg')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $treffer->getKey());
    $this->getJson('/api/v1/tasks?filter[search]=kabel')->assertJsonCount(2, 'data');
    $this->getJson('/api/v1/tasks?filter[project_id]='.$project->getKey())->assertJsonCount(2, 'data');
    $this->getJson('/api/v1/tasks?filter[phase_id]='.$phase->getKey())->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/tasks?filter[milestone_id]='.$milestone->getKey())->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/tasks?filter[assignee_id]='.$assignee->getKey())->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/tasks?filter[status]=done,todo')->assertJsonCount(2, 'data');
    $this->getJson('/api/v1/tasks?filter[priority]=urgent')->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/tasks?filter[trade]=elek')->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/tasks?filter[due_before]=2026-01-10')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $blocker->getKey());
    $this->getJson('/api/v1/tasks?filter[overdue]=true')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $treffer->getKey());
    $this->getJson('/api/v1/tasks?filter[overdue]=false')->assertJsonCount(3, 'data');
    $this->getJson('/api/v1/tasks?sort=position')->assertJsonPath('data.0.id', $treffer->getKey());
    $this->getJson('/api/v1/tasks?sort=-due_date')->assertJsonPath('data.0.title', 'Andere Aufgabe');
    $this->getJson('/api/v1/tasks?sort=title')->assertJsonPath('data.0.title', 'Andere Aufgabe');
    $this->getJson('/api/v1/tasks?sort=priority')->assertJsonPath('data.0.priority', 'low');
    $this->getJson('/api/v1/tasks?sort=-status')->assertJsonPath('data.0.status', 'todo');

    $this->getJson('/api/v1/tasks?include=project,phase,milestone,assignee,blockedBy&filter[trade]=Elektro')
        ->assertOk()
        ->assertJsonPath('data.0.project.name', 'Umbau Nord')
        ->assertJsonPath('data.0.phase.name', 'Ausführung')
        ->assertJsonPath('data.0.milestone.name', 'Rohbau')
        ->assertJsonPath('data.0.assignee.id', $assignee->getKey())
        ->assertJsonPath('data.0.blocked_by.title', 'Bestellung Kabel');
});

it('shows a task and hides tasks of other organizations', function () {
    $task = Task::factory()->create(['checklist' => [['text' => 'Material', 'done' => true]]]);
    $fremd = inOrganization(Organization::factory()->create(), fn (): Task => Task::factory()->create());

    actingAsApiUser(['projects:read']);

    $this->getJson('/api/v1/tasks')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $task->getKey());
    $this->getJson('/api/v1/tasks/'.$task->getKey().'?include=project')
        ->assertOk()
        ->assertJsonPath('data.project.id', $task->project_id)
        ->assertJsonPath('data.checklist.0.text', 'Material');
    $this->getJson('/api/v1/tasks/'.$task->getKey().'?include=nope')->assertStatus(400);
    $this->getJson('/api/v1/tasks/'.$fremd->getKey())->assertNotFound();
});

it('creates, updates and deletes tasks with tenant-scoped validation', function () {
    $project = Project::factory()->create();
    $phase = $project->phases()->create(['name' => 'Ausführung']);
    $fremdePhase = Project::factory()->create()->phases()->create(['name' => 'Anderes']);
    $assignee = User::factory()->create();
    $assignee->organizations()->attach(Filament::getTenant());
    $fremderUser = User::factory()->create();
    $fremdesProjekt = inOrganization(Organization::factory()->create(), fn (): Project => Project::factory()->create());
    $fremdeAufgabe = inOrganization(Organization::factory()->create(), fn (): Task => Task::factory()->create());

    $user = actingAsApiUser(['projects:write']);

    $this->postJson('/api/v1/tasks', ['project_id' => $fremdesProjekt->getKey(), 'title' => 'X', 'assignee_id' => $fremderUser->getKey(), 'blocked_by_task_id' => $fremdeAufgabe->getKey()])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['project_id', 'assignee_id', 'blocked_by_task_id']);

    $this->postJson('/api/v1/tasks', ['project_id' => $project->getKey(), 'title' => 'X', 'phase_id' => $fremdePhase->getKey(), 'status' => 'nope'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['phase_id', 'status']);

    $response = $this->postJson('/api/v1/tasks', [
        'project_id' => $project->getKey(),
        'phase_id' => $phase->getKey(),
        'assignee_id' => $assignee->getKey(),
        'title' => 'Kabelwege verlegen',
        'priority' => 'high',
        'trade' => 'Elektro',
        'start_date' => '2026-02-01',
        'due_date' => '2026-02-10',
        'checklist' => [['text' => 'Material bestellen', 'done' => false]],
    ])->assertCreated()
        ->assertJsonPath('data.title', 'Kabelwege verlegen')
        ->assertJsonPath('data.status', 'todo')
        ->assertJsonPath('data.priority', 'high')
        ->assertJsonPath('data.checklist.0.text', 'Material bestellen')
        ->assertJsonPath('data.assignee.id', $assignee->getKey());

    $task = Task::query()->findOrFail($response->json('data.id'));

    expect($task->organization_id)->toBe(Filament::getTenant()->getKey())
        ->and($task->created_by)->toBe($user->getKey());

    $this->patchJson('/api/v1/tasks/'.$task->getKey(), ['blocked_by_task_id' => $task->getKey()])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['blocked_by_task_id']);

    $this->patchJson('/api/v1/tasks/'.$task->getKey(), ['status' => 'done'])
        ->assertOk()
        ->assertJsonPath('data.status', 'done');

    expect($task->fresh()->completed_at)->not->toBeNull();

    $this->patchJson('/api/v1/tasks/'.$task->getKey(), ['status' => 'review'])
        ->assertOk()
        ->assertJsonPath('data.completed_at', null);

    $this->deleteJson('/api/v1/tasks/'.$task->getKey())->assertNoContent();

    expect(Task::query()->find($task->getKey()))->toBeNull();
});

it('enforces abilities and tenant isolation on writes', function () {
    $task = Task::factory()->create();
    $fremd = inOrganization(Organization::factory()->create(), fn (): Task => Task::factory()->create(['title' => 'Fremd']));

    actingAsApiUser(['projects:read']);

    $this->postJson('/api/v1/tasks', ['project_id' => $task->project_id, 'title' => 'X'])->assertForbidden();
    $this->patchJson('/api/v1/tasks/'.$task->getKey(), ['title' => 'X'])->assertForbidden();
    $this->deleteJson('/api/v1/tasks/'.$task->getKey())->assertForbidden();

    actingAsApiUser(['projects:write']);

    $this->patchJson('/api/v1/tasks/'.$fremd->getKey(), ['title' => 'X'])->assertNotFound();
    $this->deleteJson('/api/v1/tasks/'.$fremd->getKey())->assertNotFound();

    expect($fremd->fresh()->title)->toBe('Fremd');
});
