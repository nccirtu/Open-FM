<?php

namespace App\Filament\Pm\Widgets;

use App\Enums\ProjectHealth;
use App\Filament\Widgets\BaseChartWidget;
use App\Models\Project;
use Filament\Support\RawJs;

class ProjectHealthChartWidget extends BaseChartWidget
{
    public static int $gridW = 6;

    public static int $gridH = 8;

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Project::class) ?? false;
    }

    public function getHeading(): ?string
    {
        return __('app.widgets.project_health');
    }

    protected function getData(): array
    {
        $counts = Project::query()->open()->get()
            ->groupBy(fn (Project $project): string => $project->effectiveHealth()->value)
            ->map->count();

        return [
            'labels' => array_map(fn (ProjectHealth $health): string => $health->getLabel(), ProjectHealth::cases()),
            'datasets' => [
                [
                    'data' => array_map(fn (ProjectHealth $health): int => (int) ($counts[$health->value] ?? 0), ProjectHealth::cases()),
                    'backgroundColor' => ['#22c55e', '#e0b241', '#ef4444'],
                ],
            ],
        ];
    }

    protected function getOptions(): array|RawJs|null
    {
        return $this->doughnutOptions();
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
