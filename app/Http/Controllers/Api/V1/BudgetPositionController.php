<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\AuthorizesProjectChildren;
use App\Http\Requests\Api\V1\StoreBudgetPositionRequest;
use App\Http\Requests\Api\V1\UpdateBudgetPositionRequest;
use App\Http\Resources\Api\V1\BudgetPositionResource;
use App\Models\BudgetPosition;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

#[Group('Projekte')]
class BudgetPositionController extends ApiController
{
    use AuthorizesProjectChildren;

    protected static string $ability = 'projects';

    public const INCLUDES = ['project', 'costCenter', 'costEntries', 'changeOrders'];

    #[Endpoint('Budgetpositionen auflisten', 'Paginierte Liste aller Budgetpositionen des Mandanten.')]
    #[QueryParameter('filter[project_id]', description: 'Projekt-ID, mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[cost_group]', description: 'Teiltreffer in der Kostengruppe (Freitext).', type: 'string')]
    #[QueryParameter('filter[cost_center_id]', description: 'Kostenstellen-ID (Klassifikationsknoten).', type: 'string')]
    #[QueryParameter('sort', description: 'Sortierfeld, Minus für absteigend: sort, name, budget.', type: 'string', default: 'sort')]
    #[QueryParameter('include', description: 'Relationen, durch Komma getrennt: project, costCenter, costEntries, changeOrders.', type: 'string')]
    #[QueryParameter('per_page', description: 'Einträge je Seite (1–100).', type: 'integer', default: 25)]
    #[QueryParameter('page', description: 'Seite.', type: 'integer', default: 1)]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeApi('viewAny', BudgetPosition::class);

        $positions = QueryBuilder::for($this->whereProjectVisible(BudgetPosition::query()))
            ->allowedFilters(
                AllowedFilter::exact('project_id'),
                AllowedFilter::partial('cost_group'),
                AllowedFilter::exact('cost_center_id'),
            )
            ->allowedSorts('sort', 'name', 'budget')
            ->allowedIncludes(...self::INCLUDES)
            ->defaultSort('sort');

        return BudgetPositionResource::collection($this->paginate($positions, $request));
    }

    #[Endpoint('Budgetposition anzeigen')]
    #[QueryParameter('include', description: 'Relationen, durch Komma getrennt: project, costCenter, costEntries, changeOrders.', type: 'string')]
    public function show(Request $request, BudgetPosition $budgetPosition): BudgetPositionResource
    {
        abort_if($budgetPosition->project === null, 404);

        $this->authorizeApi('view', $budgetPosition);

        $budgetPosition->load($this->requestedIncludes($request, self::INCLUDES));

        return new BudgetPositionResource($budgetPosition);
    }

    #[Endpoint('Budgetposition anlegen')]
    public function store(StoreBudgetPositionRequest $request): BudgetPositionResource
    {
        $data = $request->validated();

        $this->authorizeProjectWrite($data['project_id']);

        $position = BudgetPosition::query()->create($data);

        return new BudgetPositionResource($position->load(['project', 'costCenter']));
    }

    #[Endpoint('Budgetposition ändern')]
    public function update(UpdateBudgetPositionRequest $request, BudgetPosition $budgetPosition): BudgetPositionResource
    {
        abort_if($budgetPosition->project === null, 404);

        $data = $request->validated();

        $this->authorizeProjectWrite($data['project_id'] ?? null);

        $budgetPosition->fill($data)->save();

        return new BudgetPositionResource($budgetPosition->load(['project', 'costCenter']));
    }

    #[Endpoint('Budgetposition löschen', 'Nur möglich, wenn der Position keine Kostenbuchungen und keine Nachträge zugeordnet sind.')]
    public function destroy(BudgetPosition $budgetPosition): Response
    {
        abort_if($budgetPosition->project === null, 404);

        $this->authorizeApi('delete', $budgetPosition);

        abort_if(
            $budgetPosition->costEntries()->withoutGlobalScopes()->exists() || $budgetPosition->changeOrders()->exists(),
            409,
            __('api.errors.budget_position_in_use'),
        );

        $budgetPosition->delete();

        return response()->noContent();
    }
}
