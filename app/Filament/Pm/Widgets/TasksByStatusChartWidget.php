<?php

namespace App\Filament\Pm\Widgets;

use App\Enums\TaskStatus;
use App\Filament\Widgets\BaseChartWidget;
use App\Models\Project;
use App\Models\Task;
use Filament\Support\RawJs;

class TasksByStatusChartWidget extends BaseChartWidget
{
    public static int $gridW = 6;

    public static int $gridH = 8;

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Project::class) ?? false;
    }

    public function getHeading(): ?string
    {
        return __('app.widgets.tasks_by_status');
    }

    protected function getData(): array
    {
        $counts = $this->countByEnum(
            Task::query()->whereHas('project', fn ($query) => $query->open()),
            'status',
            TaskStatus::cases(),
        );

        return [
            'labels' => $counts['labels'],
            'datasets' => [
                [
                    'data' => $counts['data'],
                    'backgroundColor' => ['#a1a1aa', '#5e81f4', '#e0b241', '#22c55e'],
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
