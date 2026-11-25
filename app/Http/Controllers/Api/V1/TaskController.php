<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TaskStatus;
use App\Http\Controllers\Api\V1\Concerns\AuthorizesProjectChildren;
use App\Http\Requests\Api\V1\StoreTaskRequest;
use App\Http\Requests\Api\V1\UpdateTaskRequest;
use App\Http\Resources\Api\V1\TaskResource;
use App\Models\Task;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

#[Group('Projekte')]
class TaskController extends ApiController
{
    use AuthorizesProjectChildren;

    protected static string $ability = 'projects';

    public const INCLUDES = ['project', 'phase', 'milestone', 'assignee', 'blockedBy'];

    #[Endpoint('Aufgaben auflisten', 'Paginierte Liste aller Projektaufgaben des Mandanten.')]
    #[QueryParameter('filter[search]', description: 'Volltext über Titel und Beschreibung.', type: 'string')]
    #[QueryParameter('filter[project_id]', description: 'Projekt-ID, mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[phase_id]', description: 'Phasen-ID, mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[milestone_id]', description: 'Meilenstein-ID, mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[assignee_id]', description: 'Zugewiesener Benutzer.', type: 'string')]
    #[QueryParameter('filter[status]', description: 'Status: todo, in_progress, review, done; mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[priority]', description: 'Priorität: low, normal, high, urgent.', type: 'string')]
    #[QueryParameter('filter[trade]', description: 'Teiltreffer im Gewerk.', type: 'string')]
    #[QueryParameter('filter[due_before]', description: 'Fällig bis Datum (Y-m-d).', type: 'string', format: 'date')]
    #[QueryParameter('filter[overdue]', description: 'true = nur überfällige offene Aufgaben.', type: 'boolean')]
    #[QueryParameter('sort', description: 'Sortierfeld, Minus für absteigend: due_date, position, priority, status, title, created_at.', type: 'string', default: '-created_at')]
    #[QueryParameter('include', description: 'Relationen, durch Komma getrennt: project, phase, milestone, assignee, blockedBy.', type: 'string')]
    #[QueryParameter('per_page', description: 'Einträge je Seite (1–100).', type: 'integer', default: 25)]
    #[QueryParameter('page', description: 'Seite.', type: 'integer', default: 1)]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeApi('viewAny', Task::class);

        $tasks = QueryBuilder::for($this->visibleTasks())
            ->allowedFilters(
                AllowedFilter::callback('search', fn (Builder $query, string $value) => $query->where(fn (Builder $inner) => $inner
                    ->where('title', 'like', "%{$value}%")
                    ->orWhere('description', 'like', "%{$value}%"))),
                AllowedFilter::exact('project_id'),
                AllowedFilter::exact('phase_id'),
                AllowedFilter::exact('milestone_id'),
                AllowedFilter::exact('assignee_id'),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('priority'),
                AllowedFilter::partial('trade'),
                AllowedFilter::callback('due_before', fn (Builder $query, string $value) => $query->whereDate('due_date', '<=', $value)),
                AllowedFilter::callback('overdue', fn (Builder $query, mixed $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN)
                    ? $query->where('status', '!=', TaskStatus::Done->value)->whereDate('due_date', '<', now()->toDateString())
                    : $query),
            )
            ->allowedSorts('due_date', 'position', 'priority', 'status', 'title', 'created_at')
            ->allowedIncludes(...self::INCLUDES)
            ->defaultSort('-created_at');

        return TaskResource::collection($this->paginate($tasks, $request));
    }

    #[Endpoint('Aufgabe anzeigen')]
    #[QueryParameter('include', description: 'Relationen, durch Komma getrennt: project, phase, milestone, assignee, blockedBy.', type: 'string')]
    public function show(Request $request, Task $task): TaskResource
    {
        $this->authorizeApi('view', $task);

        $task->load($this->requestedIncludes($request, self::INCLUDES));

        return new TaskResource($task);
    }

    #[Endpoint('Aufgabe anlegen', 'Beim Status done wird completed_at automatisch gesetzt.')]
    public function store(StoreTaskRequest $request): TaskResource
    {
        $data = $request->validated();

        $this->authorizeProjectWrite($data['project_id']);

        $task = new Task($data);
        $task->organization_id = $this->organizationId();
        $task->created_by = $request->user()->getKey();
        $task->save();

        return new TaskResource($task->load(['project', 'assignee']));
    }

    #[Endpoint('Aufgabe ändern', 'Beim Status done wird completed_at automatisch gesetzt, bei anderen Status geleert.')]
    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        $data = $request->validated();

        $this->authorizeProjectWrite($data['project_id'] ?? null);

        $task->fill($data)->save();

        return new TaskResource($task->load(['project', 'assignee']));
    }

    #[Endpoint('Aufgabe löschen')]
    public function destroy(Task $task): Response
    {
        $this->authorizeApi('delete', $task);

        $task->delete();

        return response()->noContent();
    }

    /**
     * @return Builder<Task>
     */
    private function visibleTasks(): Builder
    {
        $user = $this->apiUser();

        return Task::query()->when(
            ! $user->can('view_all_project'),
            fn (Builder $query): Builder => $query->where(fn (Builder $inner) => $inner
                ->assignedTo($user->getKey())
                ->orWhereHas('project', fn (Builder $project) => $project->managedBy($user))),
        );
    }
}
