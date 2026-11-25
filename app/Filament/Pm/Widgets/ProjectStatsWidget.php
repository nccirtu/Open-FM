<?php

namespace App\Filament\Pm\Widgets;

use App\Enums\ProjectHealth;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\Task;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class ProjectStatsWidget extends StatsOverviewWidget
{
    public static int $gridW = 12;

    public static int $gridH = 4;

    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Project::class) ?? false;
    }

    protected function getStats(): array
    {
        $open = Project::query()->open()->get();
        $red = $open->filter(fn (Project $project): bool => $project->effectiveHealth() === ProjectHealth::Red)->count();
        $planned = $open->sum(fn (Project $project): float => $project->plannedBudget());
        $actual = $open->sum(fn (Project $project): float => $project->actualCosts());

        return [
            Stat::make(__('projects.portfolio.active_projects'), $open->where('status', ProjectStatus::Active)->count())
                ->description($open->count().' '.__('projects.status.open'))
                ->icon('heroicon-o-briefcase'),
            Stat::make(__('projects.portfolio.red_projects'), $red)
                ->color($red > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-exclamation-triangle'),
            Stat::make(__('projects.portfolio.overdue_tasks'), Task::query()->open()->whereDate('due_date', '<', now()->startOfDay())->count())
                ->icon('heroicon-o-clock'),
            Stat::make(__('projects.portfolio.budget'), Number::currency($planned, 'EUR', 'de').' / '.Number::currency($actual, 'EUR', 'de'))
                ->color($planned > 0 && $actual > $planned ? 'danger' : 'gray')
                ->icon('heroicon-o-banknotes'),
        ];
    }
}
