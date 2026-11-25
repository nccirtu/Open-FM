<?php

namespace App\Filament\Integrations\GanttChart;

use App\Enums\ProjectPhaseStatus;
use App\Enums\TaskStatus;
use App\Filament\Resources\TaskResource;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\Task;
use Cafm\GanttChart\GanttWidget;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class ProjectGanttWidget extends GanttWidget
{
    public Project $project;

    protected const PROJECT_COLOR = '#3aa78d';

    protected const PHASE_PALETTE = ['#e26d5c', '#e0b241', '#5e81f4', '#9d5ce5', '#3a86a7', '#d4699e'];

    public function getHeading(): ?string
    {
        return __('projects.gantt.title');
    }

    protected function getRows(): array
    {
        $this->project->loadMissing([
            'phases.tasks' => fn ($query) => $query->with('assignee')->orderBy('start_date')->orderBy('position'),
            'phases.milestones',
            'milestones',
            'tasks' => fn ($query) => $query->with('assignee')->orderBy('start_date')->orderBy('position'),
        ]);

        $rows = [[
            'key' => 'project',
            'label' => $this->project->name,
            'type' => 'summary',
            'level' => 0,
            'group' => true,
            'parent' => null,
            'start' => $this->project->start_date?->toDateString(),
            'end' => $this->project->end_date?->toDateString(),
            'color' => self::PROJECT_COLOR,
            'assignee' => $this->project->manager?->name,
            'dependsOn' => null,
            'editable' => $this->canEditProject(),
            'done' => false,
        ]];

        foreach ($this->project->phases as $index => $phase) {
            $color = self::PHASE_PALETTE[$index % count(self::PHASE_PALETTE)];

            $rows[] = [
                'key' => "phase-{$phase->getKey()}",
                'label' => $phase->name,
                'type' => 'summary',
                'level' => 1,
                'group' => true,
                'parent' => 'project',
                'start' => $phase->start_date?->toDateString(),
                'end' => $phase->end_date?->toDateString(),
                'color' => $color,
                'assignee' => null,
                'dependsOn' => null,
                'editable' => $this->canEditProject(),
                'done' => $phase->status === ProjectPhaseStatus::Done,
            ];

            foreach ($phase->tasks as $task) {
                $rows[] = $this->taskRow($task, "phase-{$phase->getKey()}", 2, $color);
            }

            foreach ($phase->milestones as $milestone) {
                $rows[] = $this->milestoneRow($milestone, "phase-{$phase->getKey()}", 2);
            }
        }

        foreach ($this->project->tasks->whereNull('phase_id') as $task) {
            $rows[] = $this->taskRow($task, 'project', 1, self::PROJECT_COLOR);
        }

        foreach ($this->project->milestones->whereNull('phase_id') as $milestone) {
            $rows[] = $this->milestoneRow($milestone, 'project', 1);
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    protected function taskRow(Task $task, string $parent, int $level, string $phaseColor): array
    {
        return [
            'key' => "task-{$task->getKey()}",
            'label' => $task->title,
            'type' => 'bar',
            'level' => $level,
            'group' => false,
            'parent' => $parent,
            'start' => ($task->start_date ?? $task->due_date)?->toDateString(),
            'end' => ($task->due_date ?? $task->start_date)?->toDateString(),
            'color' => $phaseColor.'b3',
            'assignee' => $task->assignee?->name,
            'dependsOn' => $task->blocked_by_task_id !== null ? "task-{$task->blocked_by_task_id}" : null,
            'editable' => $this->canEditProject(),
            'done' => $task->status === TaskStatus::Done,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function milestoneRow(Milestone $milestone, string $parent, int $level): array
    {
        return [
            'key' => "milestone-{$milestone->getKey()}",
            'label' => $milestone->name,
            'type' => 'milestone',
            'level' => $level,
            'group' => false,
            'parent' => $parent,
            'start' => $milestone->due_date?->toDateString(),
            'end' => $milestone->due_date?->toDateString(),
            'color' => '#52525b',
            'assignee' => null,
            'dependsOn' => null,
            'editable' => $this->canEditProject(),
            'done' => $milestone->completed_at !== null,
        ];
    }

    protected function canEditProject(): bool
    {
        return Gate::allows('update', $this->project);
    }

    protected function handleItemMove(string $key, ?string $start, ?string $end): void
    {
        abort_unless($this->canEditProject(), 403);

        $record = $this->resolveKey($key);

        if ($record === null) {
            return;
        }

        match (true) {
            $record instanceof Task => $record->fill(['start_date' => $start, 'due_date' => $end])->save(),
            $record instanceof Milestone => $record->fill(['due_date' => $start])->save(),
            $record instanceof ProjectPhase, $record instanceof Project => $record->fill(['start_date' => $start, 'end_date' => $end])->save(),
            default => null,
        };
    }

    protected function getItemFormSchema(string $key): array
    {
        $record = $this->resolveKey($key);

        return match (true) {
            $record instanceof Task => TaskResource::formComponents($this->project->getKey(), withProject: false),
            $record instanceof ProjectPhase => [
                TextInput::make('name')->label(__('projects.phases.name'))->required()->maxLength(255),
                Select::make('status')->label(__('projects.phases.status'))->options(ProjectPhaseStatus::class)->required()->native(false),
                DatePicker::make('start_date')->label(__('projects.phases.start_date'))->native(false),
                DatePicker::make('end_date')->label(__('projects.phases.end_date'))->native(false),
            ],
            $record instanceof Milestone => [
                TextInput::make('name')->label(__('projects.milestones.name'))->required()->maxLength(255),
                DatePicker::make('due_date')->label(__('projects.milestones.due_date'))->required()->native(false),
            ],
            $record instanceof Project => [
                DatePicker::make('start_date')->label(__('projects.fields.start_date'))->native(false),
                DatePicker::make('end_date')->label(__('projects.fields.end_date'))->native(false),
            ],
            default => [],
        };
    }

    protected function fillItemForm(string $key): array
    {
        return $this->resolveKey($key)?->attributesToArray() ?? [];
    }

    protected function saveItem(string $key, array $data): void
    {
        abort_unless($this->canEditProject(), 403);

        $this->resolveKey($key)?->fill($data)->save();
    }

    protected function resolveKey(string $key): ?Model
    {
        if ($key === 'project') {
            return $this->project;
        }

        [$type, $id] = [strtok($key, '-'), substr($key, strpos($key, '-') + 1)];

        return match ($type) {
            'task' => Task::query()->where('project_id', $this->project->getKey())->find($id),
            'phase' => ProjectPhase::query()->where('project_id', $this->project->getKey())->find($id),
            'milestone' => Milestone::query()->where('project_id', $this->project->getKey())->find($id),
            default => null,
        };
    }
}
