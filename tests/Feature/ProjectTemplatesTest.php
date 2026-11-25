<?php

use App\Enums\TaskPriority;
use App\Filament\Pm\Widgets\ProjectPortfolioWidget;
use App\Filament\Pm\Widgets\ProjectStatsWidget;
use App\Filament\Resources\ProjectResource\Pages\CreateProject;
use App\Filament\Resources\ProjectResource\Pages\ViewProject;
use App\Filament\Resources\ProjectTemplateResource\Pages\CreateProjectTemplate;
use App\Models\Project;
use App\Models\ProjectTemplate;
use App\Services\ProjectTemplateApplier;
use Livewire\Livewire;

it('applies a template with relative deadlines when creating a project', function () {
    loginAsAdmin();
    $template = ProjectTemplate::factory()->create();

    Livewire::test(CreateProject::class)
        ->fillForm(['name' => 'Umbau Krefeld', 'status' => 'active', 'health' => 'green', 'start_date' => '2026-10-01', 'template_id' => $template->getKey()])
        ->call('create')
        ->assertHasNoFormErrors();

    $project = Project::query()->firstOrFail();
    $planung = $project->phases()->where('name', 'Planung')->firstOrFail();
    $task = $project->tasks()->where('title', 'Bestandsaufnahme')->firstOrFail();

    expect($project->phases()->count())->toBe(2)
        ->and($planung->end_date?->toDateString())->toBe('2026-10-31')
        ->and($project->milestones()->where('name', 'Abnahme')->first()->due_date->toDateString())->toBe('2026-12-25')
        ->and($task->phase_id)->toBe($planung->getKey())
        ->and($task->priority)->toBe(TaskPriority::High)
        ->and($task->due_date?->toDateString())->toBe('2026-10-08')
        ->and($task->checklist)->toBe([['text' => 'Aufmaß', 'done' => false], ['text' => 'Fotos', 'done' => false]])
        ->and($project->budgetPositions()->count())->toBe(2)
        ->and($project->plannedBudget())->toBe(60000.0)
        ->and($project->end_date?->toDateString())->toBe('2026-12-30');
});

it('applies a template to an existing project and captures a project as template', function () {
    loginAsAdmin();
    $template = ProjectTemplate::factory()->create();
    $project = Project::factory()->create(['start_date' => '2026-09-01', 'end_date' => '2026-11-30']);

    Livewire::test(ViewProject::class, ['record' => $project->getKey()])
        ->callAction('applyTemplate', ['template_id' => $template->getKey()])
        ->assertHasNoActionErrors()
        ->callAction('saveAsTemplate', ['name' => 'Kopie aus Projekt'])
        ->assertHasNoActionErrors();

    $captured = ProjectTemplate::query()->where('name', 'Kopie aus Projekt')->firstOrFail();

    expect($project->tasks()->count())->toBe(3)
        ->and($captured->duration_days)->toBe(90)
        ->and(collect($captured->phases)->pluck('name')->all())->toBe(['Planung', 'Ausführung'])
        ->and(collect($captured->tasks)->firstWhere('title', 'Bestandsaufnahme')['due_offset_days'])->toBe(7)
        ->and(collect($captured->milestones)->firstWhere('name', 'Abnahme')['phase'])->toBe('Ausführung')
        ->and($captured->organization_id)->toBe(defaultOrganization()->getKey());

    app(ProjectTemplateApplier::class)->apply($captured, $project);

    expect($project->tasks()->count())->toBe(6);
});

it('creates a template in the designer and shows the portfolio widgets', function () {
    loginAsAdmin();

    Livewire::test(CreateProjectTemplate::class)
        ->fillForm([
            'name' => 'Kleinprojekt',
            'type' => 'maintenance',
            'duration_days' => 30,
            'phases' => [['name' => 'Umsetzung', 'start_offset_days' => 0, 'end_offset_days' => 30]],
            'tasks' => [['title' => 'Angebot einholen', 'phase' => 'Umsetzung', 'priority' => 'normal', 'due_offset_days' => 5, 'checklist' => [['text' => 'Drei Angebote']]]],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(ProjectTemplate::query()->where('name', 'Kleinprojekt')->firstOrFail()->tasks[0]['checklist'])->toBe(['Drei Angebote']);

    Project::factory()->create(['name' => 'Rotes Projekt', 'end_date' => now()->subDay()->toDateString()]);

    Livewire::test(ProjectPortfolioWidget::class)->assertOk()->assertSee('Rotes Projekt')->assertSee('Rot');
    Livewire::test(ProjectStatsWidget::class)->assertOk()->assertSee(__('projects.portfolio.red_projects'));
});
