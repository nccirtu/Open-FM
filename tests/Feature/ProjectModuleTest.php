<?php

use App\Enums\ProjectHealth;
use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Filament\Resources\ProjectResource\Pages\CreateProject;
use App\Filament\Resources\ProjectResource\Pages\ProjectBoard;
use App\Filament\Resources\ProjectResource\Pages\ViewProject;
use App\Filament\Resources\ProjectResource\RelationManagers\MilestonesRelationManager;
use App\Filament\Resources\ProjectResource\RelationManagers\PhasesRelationManager;
use App\Filament\Resources\TaskResource\Pages\CreateTask;
use App\Filament\Resources\TaskResource\Pages\ListTasks;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Property;
use App\Models\Task;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

it('creates a project with property and manager in the PM panel', function () {
    $admin = loginAsAdmin();
    $property = Property::factory()->create();

    Livewire::test(CreateProject::class)
        ->fillForm([
            'code' => 'P-2026-01',
            'name' => 'Sanierung Köln',
            'type' => 'remodel',
            'property_id' => $property->getKey(),
            'manager_id' => $admin->getKey(),
            'status' => 'active',
            'health' => 'green',
            'start_date' => '2026-09-01',
            'end_date' => '2026-12-15',
            'budget' => 250000,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $project = Project::query()->firstOrFail();

    expect($project->status)->toBe(ProjectStatus::Active)
        ->and($project->organization_id)->toBe(defaultOrganization()->getKey())
        ->and($project->property->is($property))->toBeTrue()
        ->and($project->manager->is($admin))->toBeTrue()
        ->and($property->projects()->count())->toBe(1)
        ->and($admin->projects()->count())->toBe(1);
});

it('manages phases and milestones via relation managers and derives the traffic light', function () {
    loginAsAdmin();
    $project = Project::factory()->create();

    Livewire::test(PhasesRelationManager::class, ['ownerRecord' => $project, 'pageClass' => ViewProject::class])
        ->callAction(TestAction::make('create')->table(), ['name' => 'Planung', 'sort' => 1, 'status' => 'active'])
        ->assertHasNoActionErrors();

    Livewire::test(MilestonesRelationManager::class, ['ownerRecord' => $project, 'pageClass' => ViewProject::class])
        ->callAction(TestAction::make('create')->table(), ['name' => 'Baugenehmigung', 'phase_id' => $project->phases()->first()->getKey(), 'due_date' => now()->subDays(3)->toDateString()])
        ->assertHasNoActionErrors();

    $project = $project->fresh();
    $milestone = $project->milestones()->firstOrFail();

    expect($project->phases()->count())->toBe(1)
        ->and($milestone->isOverdue())->toBeTrue()
        ->and($project->indicators())->toContain('milestones_overdue')
        ->and($project->effectiveHealth())->toBe(ProjectHealth::Red)
        ->and($project->nextMilestone()?->is($milestone))->toBeTrue();

    Livewire::test(MilestonesRelationManager::class, ['ownerRecord' => $project, 'pageClass' => ViewProject::class])
        ->callAction(TestAction::make('complete')->table($milestone))
        ->assertHasNoActionErrors();

    expect($project->fresh()->indicators())->toBe([])
        ->and($project->fresh()->effectiveHealth())->toBe(ProjectHealth::Green);
});

it('creates tasks, tracks progress, completion and blocking', function () {
    $admin = loginAsAdmin();
    $project = Project::factory()->create();

    Livewire::test(CreateTask::class)
        ->fillForm([
            'project_id' => $project->getKey(),
            'title' => 'Bodenbelag ausschreiben',
            'status' => 'todo',
            'priority' => 'high',
            'assignee_id' => $admin->getKey(),
            'due_date' => now()->subDay()->toDateString(),
            'trade' => 'Bodenbelag',
            'checklist' => [['text' => 'Angebote einholen', 'done' => true], ['text' => 'Vergabe', 'done' => false]],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $task = Task::query()->firstOrFail();
    $blocked = Task::factory()->create(['project_id' => $project->getKey(), 'title' => 'Verlegen', 'blocked_by_task_id' => $task->getKey()]);

    expect($task->organization_id)->toBe(defaultOrganization()->getKey())
        ->and($task->isOverdue())->toBeTrue()
        ->and($task->checklistProgress())->toBe('1/2')
        ->and($blocked->isBlocked())->toBeTrue()
        ->and($project->progress())->toBe(0)
        ->and($project->indicators())->toContain('tasks_overdue')
        ->and($project->effectiveHealth())->toBe(ProjectHealth::Yellow);

    $task->update(['status' => TaskStatus::Done]);

    expect($task->fresh()->completed_at)->not->toBeNull()
        ->and($blocked->fresh()->isBlocked())->toBeFalse()
        ->and($project->progress())->toBe(50);

    Livewire::test(ListTasks::class)
        ->assertOk()
        ->set('activeTab', 'mine')
        ->assertCanNotSeeTableRecords([$task])
        ->set('activeTab', 'all')
        ->assertCanSeeTableRecords([$task, $blocked]);
});

it('renders the kanban board and moves cards between columns', function () {
    loginAsAdmin();
    $project = Project::factory()->create();
    $task = Task::factory()->create(['project_id' => $project->getKey(), 'title' => 'Elektro prüfen']);
    $other = Task::factory()->create(['title' => 'Fremdes Projekt']);

    $board = Livewire::test(ProjectBoard::class, ['record' => $project->getKey()])
        ->assertOk()
        ->assertSee('Elektro prüfen')
        ->assertDontSee('Fremdes Projekt');

    $board->call('moveCard', (string) $task->getKey(), TaskStatus::InProgress->value, null, null);

    expect($task->fresh()->status)->toBe(TaskStatus::InProgress)
        ->and($task->fresh()->position)->not->toBeNull();
});

it('scopes projects and tasks to the current tenant', function () {
    loginAsAdmin();
    $own = Project::factory()->create(['name' => 'Eigenes Projekt']);
    $foreignOrganization = Organization::factory()->create();
    $foreign = inOrganization($foreignOrganization, fn (): Project => Project::factory()->create(['name' => 'Fremdes Projekt', 'organization_id' => $foreignOrganization->getKey()]));

    expect(Project::query()->pluck('name')->all())->toBe(['Eigenes Projekt'])
        ->and($foreign->fresh()->organization_id)->toBe($foreignOrganization->getKey());

    $this->get(ProjectBoard::getUrl(['record' => $foreign->getKey()]))->assertNotFound();
    $this->get(ProjectBoard::getUrl(['record' => $own->getKey()]))->assertOk();
});
