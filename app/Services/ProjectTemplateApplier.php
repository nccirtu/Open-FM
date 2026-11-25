<?php

namespace App\Services;

use App\Enums\ProjectPhaseStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\ProjectTemplate;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class ProjectTemplateApplier
{
    public function apply(ProjectTemplate $template, Project $project): void
    {
        DB::transaction(function () use ($template, $project): void {
            $start = $project->start_date ?? now()->startOfDay();
            $phaseIds = [];
            $sort = (int) ($project->phases()->max('sort') ?? 0);

            foreach ($template->phases ?? [] as $phase) {
                if (blank($phase['name'] ?? null)) {
                    continue;
                }

                $created = $project->phases()->create([
                    'name' => $phase['name'],
                    'sort' => ++$sort,
                    'status' => ProjectPhaseStatus::Planned,
                    'start_date' => $this->dateFor($start, $phase['start_offset_days'] ?? null),
                    'end_date' => $this->dateFor($start, $phase['end_offset_days'] ?? null),
                ]);
                $phaseIds[$phase['name']] = $created->getKey();
            }

            foreach ($template->milestones ?? [] as $index => $milestone) {
                if (blank($milestone['name'] ?? null)) {
                    continue;
                }

                $project->milestones()->create([
                    'name' => $milestone['name'],
                    'phase_id' => $phaseIds[$milestone['phase'] ?? ''] ?? null,
                    'due_date' => $this->dateFor($start, $milestone['due_offset_days'] ?? null),
                    'sort' => $index + 1,
                ]);
            }

            foreach ($template->tasks ?? [] as $task) {
                if (blank($task['title'] ?? null)) {
                    continue;
                }

                $project->tasks()->create([
                    'title' => $task['title'],
                    'description' => $task['description'] ?? null,
                    'phase_id' => $phaseIds[$task['phase'] ?? ''] ?? null,
                    'status' => TaskStatus::Todo,
                    'priority' => TaskPriority::tryFrom((string) ($task['priority'] ?? '')) ?? TaskPriority::Normal,
                    'trade' => $task['trade'] ?? null,
                    'start_date' => $this->dateFor($start, $task['start_offset_days'] ?? null),
                    'due_date' => $this->dateFor($start, $task['due_offset_days'] ?? null),
                    'checklist' => collect($task['checklist'] ?? [])->map(fn (array|string $item): string => is_array($item) ? (string) ($item['text'] ?? '') : $item)->filter()->map(fn (string $text): array => ['text' => $text, 'done' => false])->values()->all() ?: null,
                ]);
            }

            $positionSort = (int) ($project->budgetPositions()->max('sort') ?? 0);

            foreach ($template->budget_positions ?? [] as $position) {
                if (blank($position['name'] ?? null)) {
                    continue;
                }

                $project->budgetPositions()->create([
                    'name' => $position['name'],
                    'cost_group' => $position['cost_group'] ?? null,
                    'cost_center_id' => $position['cost_center_id'] ?? null,
                    'budget' => (float) ($position['budget'] ?? 0),
                    'sort' => ++$positionSort,
                ]);
            }

            if ($project->end_date === null && $template->duration_days !== null) {
                $project->forceFill(['end_date' => $start->copy()->addDays((int) $template->duration_days)])->save();
            }
        });
    }

    public function captureFrom(Project $project, string $name): ProjectTemplate
    {
        $start = $project->start_date ?? $project->created_at ?? now();
        $offset = fn (?CarbonInterface $date): ?int => $date === null ? null : (int) $start->startOfDay()->diffInDays($date->startOfDay(), false);
        $phaseNames = $project->phases->keyBy('id')->map(fn (ProjectPhase $phase): string => $phase->name);

        return ProjectTemplate::query()->create([
            'name' => $name,
            'description' => $project->description,
            'type' => $project->type,
            'duration_days' => $project->end_date ? max(0, $offset($project->end_date)) : null,
            'phases' => $project->phases->map(fn (ProjectPhase $phase): array => ['name' => $phase->name, 'start_offset_days' => $offset($phase->start_date), 'end_offset_days' => $offset($phase->end_date)])->values()->all(),
            'milestones' => $project->milestones->map(fn ($milestone): array => ['name' => $milestone->name, 'phase' => $phaseNames[$milestone->phase_id] ?? null, 'due_offset_days' => $offset($milestone->due_date)])->values()->all(),
            'tasks' => $project->tasks->map(fn ($task): array => [
                'title' => $task->title,
                'description' => $task->description,
                'phase' => $phaseNames[$task->phase_id] ?? null,
                'priority' => $task->priority->value,
                'trade' => $task->trade,
                'start_offset_days' => $offset($task->start_date),
                'due_offset_days' => $offset($task->due_date),
                'checklist' => collect($task->checklist ?? [])->map(fn (array $item): string => (string) ($item['text'] ?? ''))->filter()->values()->all(),
            ])->values()->all(),
            'budget_positions' => $project->budgetPositions->map(fn ($position): array => ['name' => $position->name, 'cost_group' => $position->cost_group, 'cost_center_id' => $position->cost_center_id, 'budget' => (float) $position->budget])->values()->all(),
        ]);
    }

    private function dateFor(CarbonInterface $start, int|string|null $offsetDays): ?string
    {
        if ($offsetDays === null || $offsetDays === '') {
            return null;
        }

        return $start->copy()->addDays((int) $offsetDays)->toDateString();
    }
}
