<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\AuthorizesProjectChildren;
use App\Http\Requests\Api\V1\StoreCostEntryRequest;
use App\Http\Requests\Api\V1\UpdateCostEntryRequest;
use App\Http\Resources\Api\V1\CostEntryResource;
use App\Models\CostEntry;
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
class CostEntryController extends ApiController
{
    use AuthorizesProjectChildren;

    protected static string $ability = 'projects';

    public const INCLUDES = ['project', 'budgetPosition', 'costCenter', 'company'];

    #[Endpoint('Kostenbuchungen auflisten', 'Paginierte Liste aller Kostenbuchungen des Mandanten (auch solche an Tickets, Verträgen usw.; costable_type/costable_id sind nur lesbar).')]
    #[QueryParameter('filter[project_id]', description: 'Projekt-ID, mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[budget_position_id]', description: 'Budgetpositions-ID, mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[cost_center_id]', description: 'Kostenstellen-ID (Klassifikationsknoten).', type: 'string')]
    #[QueryParameter('filter[company_id]', description: 'Firmen-ID.', type: 'string')]
    #[QueryParameter('filter[kind]', description: 'Art: actual, estimate.', type: 'string')]
    #[QueryParameter('filter[booked_from]', description: 'Belegdatum ab (Y-m-d).', type: 'string', format: 'date')]
    #[QueryParameter('filter[booked_to]', description: 'Belegdatum bis (Y-m-d).', type: 'string', format: 'date')]
    #[QueryParameter('filter[search]', description: 'Volltext über Belegnummer, Lieferant und Beschreibung.', type: 'string')]
    #[QueryParameter('sort', description: 'Sortierfeld, Minus für absteigend: booked_on, amount, created_at.', type: 'string', default: '-booked_on')]
    #[QueryParameter('include', description: 'Relationen, durch Komma getrennt: project, budgetPosition, costCenter, company.', type: 'string')]
    #[QueryParameter('per_page', description: 'Einträge je Seite (1–100).', type: 'integer', default: 25)]
    #[QueryParameter('page', description: 'Seite.', type: 'integer', default: 1)]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeApi('viewAny', CostEntry::class);

        $entries = QueryBuilder::for($this->visibleEntries())
            ->allowedFilters(
                AllowedFilter::exact('project_id'),
                AllowedFilter::exact('budget_position_id'),
                AllowedFilter::exact('cost_center_id'),
                AllowedFilter::exact('company_id'),
                AllowedFilter::exact('kind'),
                AllowedFilter::callback('booked_from', fn (Builder $query, string $value) => $query->whereDate('booked_on', '>=', $value)),
                AllowedFilter::callback('booked_to', fn (Builder $query, string $value) => $query->whereDate('booked_on', '<=', $value)),
                AllowedFilter::callback('search', fn (Builder $query, string $value) => $query->where(fn (Builder $inner) => $inner
                    ->where('invoice_number', 'like', "%{$value}%")
                    ->orWhere('vendor', 'like', "%{$value}%")
                    ->orWhere('description', 'like', "%{$value}%"))),
            )
            ->allowedSorts('booked_on', 'amount', 'created_at')
            ->allowedIncludes(...self::INCLUDES)
            ->defaultSort('-booked_on');

        return CostEntryResource::collection($this->paginate($entries, $request));
    }

    #[Endpoint('Kostenbuchung anzeigen')]
    #[QueryParameter('include', description: 'Relationen, durch Komma getrennt: project, budgetPosition, costCenter, company.', type: 'string')]
    public function show(Request $request, CostEntry $costEntry): CostEntryResource
    {
        $this->authorizeApi('view', $costEntry);

        $costEntry->load($this->requestedIncludes($request, self::INCLUDES));

        return new CostEntryResource($costEntry);
    }

    #[Endpoint('Kostenbuchung anlegen', 'Über die API werden nur projektbezogene Buchungen angelegt; Belegdateien werden nicht unterstützt.')]
    public function store(StoreCostEntryRequest $request): CostEntryResource
    {
        $data = $request->validated();

        $this->authorizeProjectWrite($data['project_id']);

        $entry = new CostEntry($data);
        $entry->organization_id = $this->organizationId();
        $entry->created_by = $request->user()->getKey();
        $entry->save();

        return new CostEntryResource($entry->load(['project', 'budgetPosition']));
    }

    #[Endpoint('Kostenbuchung ändern')]
    public function update(UpdateCostEntryRequest $request, CostEntry $costEntry): CostEntryResource
    {
        $data = $request->validated();

        $this->authorizeProjectWrite($data['project_id'] ?? null);

        $costEntry->fill($data)->save();

        return new CostEntryResource($costEntry->load(['project', 'budgetPosition']));
    }

    #[Endpoint('Kostenbuchung löschen')]
    public function destroy(CostEntry $costEntry): Response
    {
        $this->authorizeApi('delete', $costEntry);

        $costEntry->delete();

        return response()->noContent();
    }

    /**
     * @return Builder<CostEntry>
     */
    private function visibleEntries(): Builder
    {
        $user = $this->apiUser();

        return CostEntry::query()->when(
            ! $user->can('view_all_project') || ! $user->can('view_all_property'),
            fn (Builder $query): Builder => $this->whereProjectVisible($query),
        );
    }
}
