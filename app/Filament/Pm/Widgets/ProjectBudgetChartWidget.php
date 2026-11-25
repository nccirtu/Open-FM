<?php

namespace App\Filament\Pm\Widgets;

use App\Filament\Widgets\BaseChartWidget;
use App\Models\Project;

class ProjectBudgetChartWidget extends BaseChartWidget
{
    public static int $gridW = 12;

    public static int $gridH = 8;

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Project::class) ?? false;
    }

    public function getHeading(): ?string
    {
        return __('app.widgets.project_budget');
    }

    protected function getData(): array
    {
        $projects = Project::query()->open()->orderBy('name')->limit(10)->get();

        return [
            'labels' => $projects->pluck('name')->map(fn (string $name): string => mb_strimwidth($name, 0, 24, '…'))->all(),
            'datasets' => [
                [
                    'label' => __('app.widgets.project_budget_planned'),
                    'data' => $projects->map(fn (Project $project): float => round($project->plannedBudget(), 2))->all(),
                    'backgroundColor' => self::PALETTE[0],
                ],
                [
                    'label' => __('app.widgets.project_budget_actual'),
                    'data' => $projects->map(fn (Project $project): float => round($project->actualCosts(), 2))->all(),
                    'backgroundColor' => self::PALETTE[3],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
