<?php

use App\Filament\Integrations\GanttChart\ProjectGanttWidget;
use App\Filament\Resources\ProjectResource\Pages\ProjectGantt;
use App\Models\Project;
use App\Models\ProjectTemplate;
use App\Models\Task;
use App\Services\ProjectTemplateApplier;
use Livewire\Livewire;

beforeEach(fn () => requiresPlugin('gantt-chart'));

it('renders the gantt page with phases, tasks, milestones and dependencies', function () {
    loginAsAdmin();

    $project = Project::factory()->create(['name' => 'Umbau Halle', 'start_date' => '2026-09-01', 'end_date' => '2026-10-30']);
    $phase = $project->phases()->create(['name' => 'Konzept', 'sort' => 1, 'start_date' => '2026-09-01', 'end_date' => '2026-09-15']);

    $vorgaenger = Task::factory()->create(['project_id' => $project->getKey(), 'phase_id' => $phase->getKey(), 'title' => 'Ziele festlegen', 'start_date' => '2026-09-01', 'due_date' => '2026-09-05']);
    Task::factory()->create(['project_id' => $project->getKey(), 'phase_id' => $phase->getKey(), 'title' => 'Budget planen', 'start_date' => '2026-09-06', 'due_date' => '2026-09-10', 'blocked_by_task_id' => $vorgaenger->getKey()]);

    $project->milestones()->create(['name' => 'Freigabe', 'due_date' => '2026-09-15', 'phase_id' => $phase->getKey(), 'sort' => 1]);

    Livewire::test(ProjectGantt::class, ['record' => $project->getKey()])
        ->assertSuccessful();

    Livewire::test(ProjectGanttWidget::class, ['project' => $project])
        ->assertSuccessful()
        ->assertSee('Umbau Halle')
        ->assertSee('Konzept')
        ->assertSee('Budget planen')
        ->assertSee('Freigabe')
        ->assertSee('KW37')
        ->assertSeeHtml('data-depends="task-'.$vorgaenger->getKey().'"');
});

it('moves tasks, milestones and phases via drag and drop', function () {
    loginAsAdmin();

    $project = Project::factory()->create(['start_date' => '2026-09-01', 'end_date' => '2026-10-30']);
    $phase = $project->phases()->create(['name' => 'Konzept', 'sort' => 1, 'start_date' => '2026-09-01', 'end_date' => '2026-09-15']);
    $task = Task::factory()->create(['project_id' => $project->getKey(), 'phase_id' => $phase->getKey(), 'start_date' => '2026-09-01', 'due_date' => '2026-09-05']);
    $milestone = $project->milestones()->create(['name' => 'Freigabe', 'due_date' => '2026-09-15', 'sort' => 1]);

    $widget = Livewire::test(ProjectGanttWidget::class, ['project' => $project]);

    $widget->call('moveItem', 'task-'.$task->getKey(), '2026-09-03', '2026-09-08');

    $task->refresh();
    expect($task->start_date->toDateString())->toBe('2026-09-03');
    expect($task->due_date->toDateString())->toBe('2026-09-08');

    $widget->call('moveItem', 'milestone-'.$milestone->getKey(), '2026-09-20', '2026-09-20');

    expect($milestone->refresh()->due_date->toDateString())->toBe('2026-09-20');

    $widget->call('moveItem', 'phase-'.$phase->getKey(), '2026-09-02', '2026-09-16');

    expect($phase->refresh()->end_date->toDateString())->toBe('2026-09-16');

    $fremdeAufgabe = Task::factory()->create(['start_date' => '2026-09-01', 'due_date' => '2026-09-05']);

    $widget->call('moveItem', 'task-'.$fremdeAufgabe->getKey(), '2026-01-01', '2026-01-02');

    expect($fremdeAufgabe->refresh()->due_date->toDateString())->toBe('2026-09-05');
});

it('edits a task via the gantt modal', function () {
    loginAsAdmin();

    $project = Project::factory()->create(['start_date' => '2026-09-01']);
    $task = Task::factory()->create(['project_id' => $project->getKey(), 'title' => 'Alt', 'start_date' => '2026-09-01', 'due_date' => '2026-09-05']);

    Livewire::test(ProjectGanttWidget::class, ['project' => $project])
        ->mountAction('editItem', arguments: ['key' => 'task-'.$task->getKey()])
        ->fillForm(['title' => 'Neu', 'due_date' => '2026-09-09'])
        ->callMountedAction();

    $task->refresh();
    expect($task->title)->toBe('Neu');
    expect($task->due_date->toDateString())->toBe('2026-09-09');
});

it('filters the visible period via presets and custom range', function () {
    loginAsAdmin();

    $project = Project::factory()->create(['start_date' => '2026-01-05', 'end_date' => '2026-12-18']);
    Task::factory()->create(['project_id' => $project->getKey(), 'title' => 'Frühjahr', 'start_date' => '2026-03-02', 'due_date' => '2026-03-20']);
    Task::factory()->create(['project_id' => $project->getKey(), 'title' => 'Herbst', 'start_date' => '2026-10-05', 'due_date' => '2026-10-23']);

    $widget = Livewire::test(ProjectGanttWidget::class, ['project' => $project]);

    $widget->set('rangeFrom', '2026-03-01')->set('rangeUntil', '2026-03-31');

    $instance = $widget->instance();
    $timeline = $instance->timeline();

    expect($instance->rangePreset)->toBe('custom');
    expect($timeline['days'])->toBeLessThan(50);

    $rows = collect($instance->preparedRows());

    expect($rows->firstWhere('label', 'Frühjahr')['left'])->not->toBeNull();
    expect($rows->firstWhere('label', 'Herbst')['left'])->toBeNull();

    $widget->set('rangePreset', 'this_month');

    expect($widget->get('rangeFrom'))->toBe(now()->startOfMonth()->toDateString());
    expect($widget->get('rangeUntil'))->toBe(now()->endOfMonth()->toDateString());

    $widget->set('rangePreset', 'auto');

    expect($widget->get('rangeFrom'))->toBeNull();
});

it('applies templates with task start offsets', function () {
    loginAsAdmin();

    $template = ProjectTemplate::factory()->create([
        'phases' => [['name' => 'Phase 1', 'start_offset_days' => 0, 'end_offset_days' => 10]],
        'milestones' => [],
        'tasks' => [['title' => 'Aufgabe', 'phase' => 'Phase 1', 'start_offset_days' => 2, 'due_offset_days' => 6]],
        'budget_positions' => [],
    ]);

    $project = Project::factory()->create(['start_date' => '2026-09-07']);

    app(ProjectTemplateApplier::class)->apply($template, $project);

    $task = $project->tasks()->firstOrFail();

    expect($task->start_date->toDateString())->toBe('2026-09-09');
    expect($task->due_date->toDateString())->toBe('2026-09-13');
});
