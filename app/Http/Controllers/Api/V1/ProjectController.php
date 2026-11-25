<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreProjectRequest;
use App\Http\Requests\Api\V1\UpdateProjectRequest;
use App\Http\Resources\Api\V1\ProjectResource;
use App\Models\Project;
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
class ProjectController extends ApiController
{
    protected static string $ability = 'projects';

    public const INCLUDES = ['property', 'manager', 'phases', 'milestones', 'tasks', 'budgetPositions', 'costEntries', 'changeOrders'];

    #[Endpoint('Projekte auflisten', 'Paginierte Liste aller Projekte des Mandanten.')]
    #[QueryParameter('filter[search]', description: 'Volltext über Code und Name.', type: 'string')]
    #[QueryParameter('filter[status]', description: 'Status: open, active, on_hold, done, cancelled; mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[type]', description: 'Projekttyp: new_build, remodel, renovation, maintenance, relocation, other.', type: 'string')]
    #[QueryParameter('filter[health]', description: 'Ampel: green, yellow, red.', type: 'string')]
    #[QueryParameter('filter[property_id]', description: 'Objekt-ID, mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[manager_id]', description: 'Projektleiter-ID (Benutzer).', type: 'string')]
    #[QueryParameter('filter[start_from]', description: 'Projektstart ab Datum (Y-m-d).', type: 'string', format: 'date')]
    #[QueryParameter('filter[end_before]', description: 'Projektende bis Datum (Y-m-d).', type: 'string', format: 'date')]
    #[QueryParameter('sort', description: 'Sortierfeld, Minus für absteigend: name, code, start_date, end_date, created_at.', type: 'string', default: '-created_at')]
    #[QueryParameter('include', description: 'Relationen, durch Komma getrennt: property, manager, phases, milestones, tasks, budgetPositions, costEntries, changeOrders.', type: 'string')]
    #[QueryParameter('per_page', description: 'Einträge je Seite (1–100).', type: 'integer', default: 25)]
    #[QueryParameter('page', description: 'Seite.', type: 'integer', default: 1)]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeApi('viewAny', Project::class);

        $projects = QueryBuilder::for(Project::query()->visibleTo($this->apiUser()))
            ->allowedFilters(
                AllowedFilter::callback('search', fn (Builder $query, string $value) => $query->where(fn (Builder $inner) => $inner
                    ->where('code', 'like', "%{$value}%")
                    ->orWhere('name', 'like', "%{$value}%"))),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('type'),
                AllowedFilter::exact('health'),
                AllowedFilter::exact('property_id'),
                AllowedFilter::exact('manager_id'),
                AllowedFilter::callback('start_from', fn (Builder $query, string $value) => $query->whereDate('start_date', '>=', $value)),
                AllowedFilter::callback('end_before', fn (Builder $query, string $value) => $query->whereDate('end_date', '<=', $value)),
            )
            ->allowedSorts('name', 'code', 'start_date', 'end_date', 'created_at')
            ->allowedIncludes(...self::INCLUDES)
            ->defaultSort('-created_at');

        return ProjectResource::collection($this->paginate($projects, $request));
    }

    #[Endpoint('Projekt anzeigen')]
    #[QueryParameter('include', description: 'Relationen, durch Komma getrennt: property, manager, phases, milestones, tasks, budgetPositions, costEntries, changeOrders.', type: 'string')]
    public function show(Request $request, Project $project): ProjectResource
    {
        $this->authorizeApi('view', $project);

        $project->load($this->requestedIncludes($request, self::INCLUDES));

        return new ProjectResource($project);
    }

    #[Endpoint('Projekt anlegen')]
    public function store(StoreProjectRequest $request): ProjectResource
    {
        $project = new Project($this->enforceManagerAssignment($request->validated(), Project::class));
        $project->organization_id = $this->organizationId();
        $project->created_by = $request->user()->getKey();
        $project->save();

        return new ProjectResource($project->load(['property', 'manager']));
    }

    #[Endpoint('Projekt ändern')]
    public function update(UpdateProjectRequest $request, Project $project): ProjectResource
    {
        $project->fill($this->enforceManagerAssignment($request->validated(), $project))->save();

        return new ProjectResource($project->load(['property', 'manager']));
    }

    #[Endpoint('Projekt löschen', 'Löscht das Projekt samt Phasen, Meilensteinen, Aufgaben, Budgetpositionen, Kostenbuchungen und Nachträgen.')]
    public function destroy(Project $project): Response
    {
        $this->authorizeApi('delete', $project);

        $project->delete();

        return response()->noContent();
    }
}
