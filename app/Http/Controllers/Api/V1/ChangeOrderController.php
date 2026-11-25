<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ChangeOrderStatus;
use App\Http\Controllers\Api\V1\Concerns\AuthorizesProjectChildren;
use App\Http\Requests\Api\V1\StoreChangeOrderRequest;
use App\Http\Requests\Api\V1\UpdateChangeOrderRequest;
use App\Http\Resources\Api\V1\ChangeOrderResource;
use App\Models\ChangeOrder;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

#[Group('Projekte')]
class ChangeOrderController extends ApiController
{
    use AuthorizesProjectChildren;

    protected static string $ability = 'projects';

    public const INCLUDES = ['project', 'budgetPosition', 'decider'];

    #[Endpoint('Nachträge auflisten', 'Paginierte Liste aller Nachträge des Mandanten.')]
    #[QueryParameter('filter[project_id]', description: 'Projekt-ID, mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[status]', description: 'Status: pending, approved, rejected; mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[budget_position_id]', description: 'Budgetpositions-ID, mehrere durch Komma.', type: 'string')]
    #[QueryParameter('sort', description: 'Sortierfeld, Minus für absteigend: requested_on, amount, status.', type: 'string', default: '-requested_on')]
    #[QueryParameter('include', description: 'Relationen, durch Komma getrennt: project, budgetPosition, decider.', type: 'string')]
    #[QueryParameter('per_page', description: 'Einträge je Seite (1–100).', type: 'integer', default: 25)]
    #[QueryParameter('page', description: 'Seite.', type: 'integer', default: 1)]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeApi('viewAny', ChangeOrder::class);

        $changeOrders = QueryBuilder::for($this->whereProjectVisible(ChangeOrder::query()))
            ->allowedFilters(
                AllowedFilter::exact('project_id'),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('budget_position_id'),
            )
            ->allowedSorts('requested_on', 'amount', 'status')
            ->allowedIncludes(...self::INCLUDES)
            ->defaultSort('-requested_on');

        return ChangeOrderResource::collection($this->paginate($changeOrders, $request));
    }

    #[Endpoint('Nachtrag anzeigen')]
    #[QueryParameter('include', description: 'Relationen, durch Komma getrennt: project, budgetPosition, decider.', type: 'string')]
    public function show(Request $request, ChangeOrder $changeOrder): ChangeOrderResource
    {
        abort_if($changeOrder->project === null, 404);

        $this->authorizeApi('view', $changeOrder);

        $changeOrder->load($this->requestedIncludes($request, self::INCLUDES));

        return new ChangeOrderResource($changeOrder);
    }

    #[Endpoint('Nachtrag anlegen', 'Wird der Status direkt auf approved oder rejected gesetzt, werden decided_at und decided_by aus dem Token-Benutzer belegt.')]
    public function store(StoreChangeOrderRequest $request): ChangeOrderResource
    {
        $data = $request->validated();
        $status = isset($data['status']) ? ChangeOrderStatus::from($data['status']) : ChangeOrderStatus::Pending;
        unset($data['status']);

        $this->authorizeProjectWrite($data['project_id']);

        if ($status !== ChangeOrderStatus::Pending) {
            $this->authorizeApi('decide', new ChangeOrder(['project_id' => $data['project_id']]));
        }

        $changeOrder = ChangeOrder::query()->create($data);

        if ($status !== ChangeOrderStatus::Pending) {
            $changeOrder->decide($status, $request->user()->getKey());
        }

        return new ChangeOrderResource($changeOrder->load(['project', 'budgetPosition', 'decider']));
    }

    #[Endpoint('Nachtrag ändern', 'Ein Statuswechsel auf approved oder rejected setzt decided_at und decided_by; zurück auf pending leert beide Felder.')]
    public function update(UpdateChangeOrderRequest $request, ChangeOrder $changeOrder): ChangeOrderResource
    {
        abort_if($changeOrder->project === null, 404);

        $data = $request->validated();
        $status = isset($data['status']) ? ChangeOrderStatus::from($data['status']) : null;
        unset($data['status']);

        $this->authorizeProjectWrite($data['project_id'] ?? null);

        if ($status !== null && $status !== $changeOrder->status) {
            $this->authorizeApi('decide', $changeOrder);
        }

        $changeOrder->fill($data)->save();

        if ($status !== null && $status !== $changeOrder->status) {
            $status === ChangeOrderStatus::Pending
                ? $changeOrder->forceFill(['status' => $status, 'decided_at' => null, 'decided_by' => null])->save()
                : $changeOrder->decide($status, $request->user()->getKey());
        }

        return new ChangeOrderResource($changeOrder->load(['project', 'budgetPosition', 'decider']));
    }

    #[Endpoint('Nachtrag löschen')]
    public function destroy(ChangeOrder $changeOrder): Response
    {
        abort_if($changeOrder->project === null, 404);

        $this->authorizeApi('delete', $changeOrder);

        $changeOrder->delete();

        return response()->noContent();
    }
}
