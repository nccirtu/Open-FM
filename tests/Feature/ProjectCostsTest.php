<?php

use App\Enums\ChangeOrderStatus;
use App\Enums\ProjectHealth;
use App\Filament\Resources\ProjectResource\Pages\ViewProject;
use App\Filament\Resources\ProjectResource\RelationManagers\BudgetPositionsRelationManager;
use App\Filament\Resources\ProjectResource\RelationManagers\ChangeOrdersRelationManager;
use App\Filament\Resources\ProjectResource\RelationManagers\CostEntriesRelationManager;
use App\Models\Project;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

it('tracks planned budget, actual costs, change orders and variance per position and project', function () {
    loginAsAdmin();
    $project = Project::factory()->create(['budget' => 100000]);

    expect($project->plannedBudget())->toBe(100000.0)
        ->and($project->budgetUsage())->toBe(0);

    Livewire::test(BudgetPositionsRelationManager::class, ['ownerRecord' => $project, 'pageClass' => ViewProject::class])
        ->callAction(TestAction::make('create')->table(), ['name' => 'Elektro', 'cost_group' => '440', 'budget' => 40000, 'sort' => 1])
        ->assertHasNoActionErrors()
        ->callAction(TestAction::make('create')->table(), ['name' => 'Bodenbelag', 'cost_group' => '350', 'budget' => 20000, 'sort' => 2])
        ->assertHasNoActionErrors();

    $elektro = $project->budgetPositions()->where('name', 'Elektro')->firstOrFail();

    expect($project->plannedBudget())->toBe(60000.0);

    Livewire::test(CostEntriesRelationManager::class, ['ownerRecord' => $project, 'pageClass' => ViewProject::class])
        ->callAction(TestAction::make('create')->table(), ['budget_position_id' => $elektro->getKey(), 'kind' => 'actual', 'amount' => 45000, 'booked_on' => '2026-09-01', 'vendor' => 'Elektro Müller'])
        ->assertHasNoActionErrors()
        ->callAction(TestAction::make('create')->table(), ['budget_position_id' => $elektro->getKey(), 'kind' => 'estimate', 'amount' => 5000, 'booked_on' => '2026-09-02'])
        ->assertHasNoActionErrors();

    expect($elektro->actual())->toBe(45000.0)
        ->and($elektro->estimated())->toBe(5000.0)
        ->and($elektro->variance())->toBe(-5000.0)
        ->and($project->actualCosts())->toBe(45000.0)
        ->and($project->budgetVariance())->toBe(15000.0)
        ->and($project->budgetUsage())->toBe(75);

    Livewire::test(ChangeOrdersRelationManager::class, ['ownerRecord' => $project, 'pageClass' => ViewProject::class])
        ->callAction(TestAction::make('create')->table(), ['title' => 'Mehraufwand Kabelwege', 'budget_position_id' => $elektro->getKey(), 'amount' => 8000, 'requested_on' => '2026-09-03'])
        ->assertHasNoActionErrors();

    $changeOrder = $project->changeOrders()->firstOrFail();

    expect($changeOrder->status)->toBe(ChangeOrderStatus::Pending)
        ->and($elektro->plannedBudget())->toBe(40000.0);

    Livewire::test(ChangeOrdersRelationManager::class, ['ownerRecord' => $project, 'pageClass' => ViewProject::class])
        ->callAction(TestAction::make('approve')->table($changeOrder))
        ->assertHasNoActionErrors();

    expect($changeOrder->fresh()->status)->toBe(ChangeOrderStatus::Approved)
        ->and($changeOrder->fresh()->decided_by)->toBe(auth()->id())
        ->and($elektro->plannedBudget())->toBe(48000.0)
        ->and($elektro->variance())->toBe(3000.0)
        ->and($project->plannedBudget())->toBe(68000.0);

    Livewire::test(ViewProject::class, ['record' => $project->getKey()])
        ->assertOk()
        ->assertSee('Kosten')
        ->assertSee('Budgetpositionen');
});

it('turns the traffic light red when the budget is exceeded', function () {
    loginAsAdmin();
    $project = Project::factory()->create(['budget' => 10000]);
    $project->costEntries()->create(['kind' => 'actual', 'amount' => 12000, 'booked_on' => now()]);

    expect($project->indicators())->toContain('budget_exceeded')
        ->and($project->effectiveHealth())->toBe(ProjectHealth::Red);

    $project->costEntries()->delete();
    $project->costEntries()->create(['kind' => 'actual', 'amount' => 9500, 'booked_on' => now()]);

    expect($project->indicators())->toContain('budget_nearly_used')
        ->and($project->effectiveHealth())->toBe(ProjectHealth::Yellow);
});
