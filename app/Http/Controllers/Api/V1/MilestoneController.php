<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\AuthorizesProjectChildren;
use App\Http\Requests\Api\V1\StoreMilestoneRequest;
use App\Http\Requests\Api\V1\UpdateMilestoneRequest;
use App\Http\Resources\Api\V1\MilestoneResource;
use App\Models\Milestone;
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
class MilestoneController extends ApiController
{
    use AuthorizesProjectChildren;

    protected static string $ability = 'projects';

    public const INCLUDES = ['project', 'phase'];

    #[Endpoint('Meilensteine auflisten', 'Paginierte Liste aller Meilensteine des Mandanten.')]
    #[QueryParameter('filter[project_id]', description: 'Projekt-ID, mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[phase_id]', description: 'Phasen-ID, mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[completed]', description: 'true = nur erledigte, false = nur offene Meilensteine.', type: 'boolean')]
    #[QueryParameter('filter[due_before]', description: 'Fällig bis Datum (Y-m-d).', type: 'string', format: 'date')]
    #[QueryParameter('sort', description: 'Sortierfeld, Minus für absteigend: due_date, sort, name.', type: 'string', default: 'due_date')]
    #[QueryParameter('include', description: 'Relationen, durch Komma getrennt: project, phase.', type: 'string')]
    #[QueryParameter('per_page', description: 'Einträge je Seite (1–100).', type: 'integer', default: 25)]
    #[QueryParameter('page', description: 'Seite.', type: 'integer', default: 1)]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeApi('viewAny', Milestone::class);

        $milestones = QueryBuilder::for($this->whereProjectVisible(Milestone::query()))
            ->allowedFilters(
                AllowedFilter::exact('project_id'),
                AllowedFilter::exact('phase_id'),
                AllowedFilter::callback('completed', fn (Builder $query, mixed $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN)
                    ? $query->whereNotNull('completed_at')
                    : $query->whereNull('completed_at')),
                AllowedFilter::callback('due_before', fn (Builder $query, string $value) => $query->whereDate('due_date', '<=', $value)),
            )
            ->allowedSorts('due_date', 'sort', 'name')
            ->allowedIncludes(...self::INCLUDES)
            ->defaultSort('due_date');

        return MilestoneResource::collection($this->paginate($milestones, $request));
    }

    #[Endpoint('Meilenstein anzeigen')]
    #[QueryParameter('include', description: 'Relationen, durch Komma getrennt: project, phase.', type: 'string')]
    public function show(Request $request, Milestone $milestone): MilestoneResource
    {
        abort_if($milestone->project === null, 404);

        $this->authorizeApi('view', $milestone);

        $milestone->load($this->requestedIncludes($request, self::INCLUDES));

        return new MilestoneResource($milestone);
    }

    #[Endpoint('Meilenstein anlegen')]
    public function store(StoreMilestoneRequest $request): MilestoneResource
    {
        $data = $request->validated();

        $this->authorizeProjectWrite($data['project_id']);

        $milestone = Milestone::query()->create($data);

        return new MilestoneResource($milestone->load(['project', 'phase']));
    }

    #[Endpoint('Meilenstein ändern')]
    public function update(UpdateMilestoneRequest $request, Milestone $milestone): MilestoneResource
    {
        abort_if($milestone->project === null, 404);

        $data = $request->validated();

        $this->authorizeProjectWrite($data['project_id'] ?? null);

        $milestone->fill($data)->save();

        return new MilestoneResource($milestone->load(['project', 'phase']));
    }

    #[Endpoint('Meilenstein löschen')]
    public function destroy(Milestone $milestone): Response
    {
        abort_if($milestone->project === null, 404);

        $this->authorizeApi('delete', $milestone);

        $milestone->delete();

        return response()->noContent();
    }
}
