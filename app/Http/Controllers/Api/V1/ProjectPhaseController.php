<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\AuthorizesProjectChildren;
use App\Http\Requests\Api\V1\StoreProjectPhaseRequest;
use App\Http\Requests\Api\V1\UpdateProjectPhaseRequest;
use App\Http\Resources\Api\V1\ProjectPhaseResource;
use App\Models\ProjectPhase;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

#[Group('Projekte')]
class ProjectPhaseController extends ApiController
{
    use AuthorizesProjectChildren;

    protected static string $ability = 'projects';

    public const INCLUDES = ['project', 'milestones', 'tasks'];

    #[Endpoint('Projektphasen auflisten', 'Paginierte Liste aller Projektphasen des Mandanten.')]
    #[QueryParameter('filter[project_id]', description: 'Projekt-ID, mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[status]', description: 'Status: planned, active, done.', type: 'string')]
    #[QueryParameter('sort', description: 'Sortierfeld, Minus für absteigend: sort, start_date, name.', type: 'string', default: 'sort')]
    #[QueryParameter('include', description: 'Relationen, durch Komma getrennt: project, milestones, tasks.', type: 'string')]
    #[QueryParameter('per_page', description: 'Einträge je Seite (1–100).', type: 'integer', default: 25)]
    #[QueryParameter('page', description: 'Seite.', type: 'integer', default: 1)]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeApi('viewAny', ProjectPhase::class);

        $phases = QueryBuilder::for($this->whereProjectVisible(ProjectPhase::query()))
            ->allowedFilters(
                AllowedFilter::exact('project_id'),
                AllowedFilter::exact('status'),
            )
            ->allowedSorts('sort', 'start_date', 'name')
            ->allowedIncludes(...self::INCLUDES)
            ->defaultSort('sort');

        return ProjectPhaseResource::collection($this->paginate($phases, $request));
    }

    #[Endpoint('Projektphase anzeigen')]
    #[QueryParameter('include', description: 'Relationen, durch Komma getrennt: project, milestones, tasks.', type: 'string')]
    public function show(Request $request, ProjectPhase $projectPhase): ProjectPhaseResource
    {
        abort_if($projectPhase->project === null, 404);

        $this->authorizeApi('view', $projectPhase);

        $projectPhase->load($this->requestedIncludes($request, self::INCLUDES));

        return new ProjectPhaseResource($projectPhase);
    }

    #[Endpoint('Projektphase anlegen')]
    public function store(StoreProjectPhaseRequest $request): ProjectPhaseResource
    {
        $data = $request->validated();

        $this->authorizeProjectWrite($data['project_id']);

        $phase = ProjectPhase::query()->create($data);

        return new ProjectPhaseResource($phase->load('project'));
    }

    #[Endpoint('Projektphase ändern')]
    public function update(UpdateProjectPhaseRequest $request, ProjectPhase $projectPhase): ProjectPhaseResource
    {
        abort_if($projectPhase->project === null, 404);

        $data = $request->validated();

        $this->authorizeProjectWrite($data['project_id'] ?? null);

        $projectPhase->fill($data)->save();

        return new ProjectPhaseResource($projectPhase->load('project'));
    }

    #[Endpoint('Projektphase löschen', 'Zugeordnete Meilensteine und Aufgaben bleiben erhalten und verlieren die Phasenzuordnung.')]
    public function destroy(ProjectPhase $projectPhase): Response
    {
        abort_if($projectPhase->project === null, 404);

        $this->authorizeApi('delete', $projectPhase);

        $projectPhase->delete();

        return response()->noContent();
    }
}
