<?php

use App\Enums\TicketStatus;
use App\Filament\Contracts\Pages\ContractsDashboard;
use App\Filament\Contracts\Widgets\ContractCostsByTypeChartWidget;
use App\Filament\Contracts\Widgets\ContractsByTypeChartWidget;
use App\Filament\Contracts\Widgets\ExpiringContractsWidget;
use App\Filament\Fm\Pages\FmDashboard;
use App\Filament\Fm\Widgets\AssetConditionChartWidget;
use App\Filament\Fm\Widgets\AssetsByServiceStatusChartWidget;
use App\Filament\Fm\Widgets\AssetsByTypeChartWidget;
use App\Filament\Fm\Widgets\CostsPerMonthChartWidget;
use App\Filament\Fm\Widgets\MaintenanceCompletedChartWidget;
use App\Filament\Fm\Widgets\OpenTicketsWidget;
use App\Filament\Fm\Widgets\TicketsByCategoryChartWidget;
use App\Filament\Fm\Widgets\TicketsByPriorityChartWidget;
use App\Filament\Fm\Widgets\TicketsPerPropertyChartWidget;
use App\Filament\Fm\Widgets\TicketStatsWidget;
use App\Filament\Fm\Widgets\TicketTrendChartWidget;
use App\Filament\Fm\Widgets\UpcomingMaintenanceWidget;
use App\Filament\Pm\Pages\PmDashboard;
use App\Filament\Pm\Widgets\MyTasksWidget;
use App\Filament\Pm\Widgets\ProjectBudgetChartWidget;
use App\Filament\Pm\Widgets\ProjectHealthChartWidget;
use App\Filament\Pm\Widgets\TasksByStatusChartWidget;
use App\Filament\Pm\Widgets\UpcomingMilestonesWidget;
use App\Models\Contract;
use App\Models\Project;
use App\Models\Task;
use App\Models\Ticket;
use Filament\Facades\Filament;
use Illuminate\Support\Collection;
use JohnRivera7\FilamentWidgetGrid\Models\WidgetGridLayout;
use Livewire\Livewire;

it('renders the customizable fm dashboard', function () {
    loginAsAdminInFmPanel();

    Livewire::test(FmDashboard::class)
        ->assertSuccessful();
});

it('renders the new dashboard widgets', function () {
    loginAsAdminInFmPanel();

    $ticket = Ticket::factory()->create(['title' => 'Heizung ausgefallen', 'status' => TicketStatus::Reported]);

    Livewire::test(OpenTicketsWidget::class)
        ->assertSuccessful()
        ->assertSee('Heizung ausgefallen');

    Livewire::test(UpcomingMaintenanceWidget::class)->assertSuccessful();
    Livewire::test(AssetConditionChartWidget::class)->assertSuccessful();
    Livewire::test(TicketTrendChartWidget::class)->assertSuccessful();

    $contract = Contract::factory()->create(['name' => 'Aufzugswartung', 'status' => 'active', 'end_date' => now()->addDays(60), 'notice_period_days' => 30]);

    Livewire::test(ExpiringContractsWidget::class)
        ->assertSuccessful()
        ->assertSee('Aufzugswartung');

    $project = Project::factory()->create();
    Task::factory()->create(['project_id' => $project->getKey(), 'title' => 'Angebot einholen', 'assignee_id' => auth()->id()]);
    $project->milestones()->create(['name' => 'Rohbau fertig', 'due_date' => now()->addDays(10), 'sort' => 1]);

    Livewire::test(MyTasksWidget::class)
        ->assertSuccessful()
        ->assertSee('Angebot einholen');

    Livewire::test(UpcomingMilestonesWidget::class)
        ->assertSuccessful()
        ->assertSee('Rohbau fertig');
});

it('renders all chart widgets with tooltip options', function () {
    loginAsAdminInFmPanel();

    Ticket::factory()->create();

    $charts = [
        TicketsByCategoryChartWidget::class,
        TicketsByPriorityChartWidget::class,
        TicketsPerPropertyChartWidget::class,
        MaintenanceCompletedChartWidget::class,
        AssetsByTypeChartWidget::class,
        AssetsByServiceStatusChartWidget::class,
        CostsPerMonthChartWidget::class,
        TasksByStatusChartWidget::class,
        ProjectBudgetChartWidget::class,
        ProjectHealthChartWidget::class,
        ContractsByTypeChartWidget::class,
        ContractCostsByTypeChartWidget::class,
    ];

    foreach ($charts as $chart) {
        Livewire::test($chart)->assertSuccessful();

        $widget = new $chart;
        $options = Closure::bind(fn (): array => $this->getOptions(), $widget, $chart)();
        expect($options['plugins']['tooltip']['enabled'] ?? null)->toBeTrue($chart);
    }
});

it('runs the dashboard grid with twelve columns in every panel', function (string $panel) {
    expect(Filament::getPanel($panel)->getPlugin('filament-widget-grid')->getColumns())->toBe(12);
})->with(['fm', 'pm', 'contracts', 'portal']);

it('packs the default dashboards into full rows', function (string $panel, string $dashboard) {
    match ($panel) {
        'fm' => loginAsAdminInFmPanel(),
        'contracts' => loginAsAdminInContractsPanel(),
        default => loginAsAdmin(),
    };

    $rows = collect(Livewire::test($dashboard)->get('widgetGridLayout'))->groupBy('y');

    expect($rows)->not->toBeEmpty();

    $rows->each(fn (Collection $row) => expect($row->sum('w'))->toBe(
        12,
        $row->pluck('widget')->map(fn (string $widget): string => class_basename($widget))->implode(', '),
    ));
})->with([
    'fm' => ['fm', FmDashboard::class],
    'pm' => ['pm', PmDashboard::class],
    'contracts' => ['contracts', ContractsDashboard::class],
]);

it('names the records of an empty table widget in the user language', function () {
    loginAsAdmin();

    Livewire::test(MyTasksWidget::class)
        ->assertSee(__('filament-tables::table.empty.heading', ['model' => __('projects.tasks.models')]))
        ->assertDontSee(__('filament-tables::table.empty.heading', ['model' => 'tasks']));
});

it('relabels stored 24-column layouts as 12 columns when migrating', function () {
    loginAsAdminInFmPanel();

    $items = [['widget' => TicketStatsWidget::class, 'x' => 0, 'y' => 0, 'w' => 12, 'h' => 3, 'visible' => true]];
    $layout = WidgetGridLayout::query()->create([
        'user_id' => auth()->id(),
        'panel_id' => 'fm',
        'is_default' => false,
        'items' => ['columns' => 24, 'cellHeight' => 45, 'items' => $items],
    ]);

    (require database_path('migrations/2026_10_03_203605_rescale_stored_widget_grid_layouts_to_twelve_columns.php'))->up();

    expect($layout->fresh()->items)->toBe(['columns' => 12, 'cellHeight' => 45, 'items' => $items])
        ->and(Livewire::test(FmDashboard::class)->get('widgetGridLayout')[0]['w'])->toBe(12);
});

it('saves a personal widget layout', function () {
    loginAsAdminInFmPanel();

    $layout = [
        ['widget' => TicketStatsWidget::class, 'x' => 0, 'y' => 0, 'w' => 24, 'h' => 4, 'visible' => true],
    ];

    Livewire::test(FmDashboard::class)
        ->set('widgetGridLayout', $layout)
        ->call('saveWidgetGridLayout');

    expect(WidgetGridLayout::query()
        ->where('user_id', auth()->id())
        ->where('panel_id', 'fm')
        ->exists())->toBeTrue();
});
