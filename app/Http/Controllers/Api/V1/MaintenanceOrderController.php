<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\MaintenanceOrderStatus;
use App\Http\Requests\Api\V1\StoreMaintenanceOrderRequest;
use App\Http\Requests\Api\V1\UpdateMaintenanceOrderRequest;
use App\Http\Resources\Api\V1\MaintenanceOrderResource;
use App\Models\MaintenanceOrder;
use App\Support\ServicePartner;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

#[Group('Wartung')]
class MaintenanceOrderController extends ApiController
{
    protected static string $ability = 'maintenance';

    public const INCLUDES = ['plan', 'asset', 'completedBy'];

    #[Endpoint('Wartungsaufträge auflisten', 'Paginierte Liste aller Wartungs- und Prüfaufträge des Mandanten.')]
    #[QueryParameter('filter[status]', description: 'Status: open, completed, skipped; mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[asset_id]', description: 'Anlagen-ID, mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[maintenance_plan_id]', description: 'Wartungsplan-ID, mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[property_id]', description: 'Objekt-ID der Anlage, mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[due_before]', description: 'Fällig bis Datum (Y-m-d).', type: 'string', format: 'date')]
    #[QueryParameter('filter[due_after]', description: 'Fällig ab Datum (Y-m-d).', type: 'string', format: 'date')]
    #[QueryParameter('filter[overdue]', description: 'Nur überfällige offene Aufträge (true) bzw. alle übrigen (false).', type: 'boolean')]
    #[QueryParameter('sort', description: 'Sortierfeld, Minus für absteigend: due_at, status, created_at.', type: 'string', default: 'due_at')]
    #[QueryParameter('include', description: 'Relationen, durch Komma getrennt: plan, asset, completedBy.', type: 'string')]
    #[QueryParameter('per_page', description: 'Einträge je Seite (1–100).', type: 'integer', default: 25)]
    #[QueryParameter('page', description: 'Seite.', type: 'integer', default: 1)]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeApi('viewAny', MaintenanceOrder::class);

        $orders = QueryBuilder::for($this->visibleOrders())
            ->allowedFilters(
                AllowedFilter::exact('status'),
                AllowedFilter::exact('asset_id'),
                AllowedFilter::exact('maintenance_plan_id'),
                AllowedFilter::callback('property_id', fn (Builder $query, string|array $value) => $query->whereHas('asset', fn (Builder $asset) => $asset->whereIn('property_id', (array) $value))),
                AllowedFilter::callback('due_before', fn (Builder $query, string $value) => $query->whereDate('due_at', '<=', $value)),
                AllowedFilter::callback('due_after', fn (Builder $query, string $value) => $query->whereDate('due_at', '>=', $value)),
                AllowedFilter::callback('overdue', function (Builder $query, mixed $value): void {
                    $overdue = fn (Builder $inner) => $inner
                        ->where('status', MaintenanceOrderStatus::Open)
                        ->whereDate('due_at', '<', today());

                    filter_var($value, FILTER_VALIDATE_BOOLEAN)
                        ? $query->where($overdue)
                        : $query->whereNot($overdue);
                }),
            )
            ->allowedSorts('due_at', 'status', 'created_at')
            ->allowedIncludes(...self::INCLUDES)
            ->defaultSort('due_at');

        return MaintenanceOrderResource::collection($this->paginate($orders, $request));
    }

    #[Endpoint('Wartungsauftrag anzeigen')]
    #[QueryParameter('include', description: 'Relationen, durch Komma getrennt: plan, asset, completedBy.', type: 'string')]
    public function show(Request $request, MaintenanceOrder $maintenanceOrder): MaintenanceOrderResource
    {
        $this->authorizeOrder('view', $maintenanceOrder);

        $maintenanceOrder->load($this->requestedIncludes($request, self::INCLUDES));

        return new MaintenanceOrderResource($maintenanceOrder);
    }

    #[Endpoint('Wartungsauftrag anlegen', 'Je Wartungsplan und Fälligkeitsdatum ist nur ein Auftrag möglich.')]
    public function store(StoreMaintenanceOrderRequest $request): MaintenanceOrderResource
    {
        $validated = $request->validated();

        $this->abortOnDuplicateDueDate($validated['maintenance_plan_id'], $validated['due_at']);

        $order = new MaintenanceOrder($validated);
        $order->organization_id = $this->organizationId();
        $this->applyCompletion($order, $request);
        $order->save();

        return new MaintenanceOrderResource($order->load(['plan', 'asset']));
    }

    #[Endpoint('Wartungsauftrag ändern', 'Der Statuswechsel auf completed setzt Abschlusszeitpunkt und Token-Benutzer als Erlediger.')]
    public function update(UpdateMaintenanceOrderRequest $request, MaintenanceOrder $maintenanceOrder): MaintenanceOrderResource
    {
        $this->authorizeOrder('update', $maintenanceOrder);

        $maintenanceOrder->fill($request->validated());

        if ($maintenanceOrder->isDirty(['maintenance_plan_id', 'due_at'])) {
            $this->abortOnDuplicateDueDate($maintenanceOrder->maintenance_plan_id, $maintenanceOrder->due_at, $maintenanceOrder->getKey());
        }

        $this->applyCompletion($maintenanceOrder, $request);
        $maintenanceOrder->save();

        return new MaintenanceOrderResource($maintenanceOrder->load(['plan', 'asset']));
    }

    #[Endpoint('Wartungsauftrag löschen')]
    public function destroy(MaintenanceOrder $maintenanceOrder): Response
    {
        $this->authorizeOrder('delete', $maintenanceOrder);

        $maintenanceOrder->delete();

        return response()->noContent();
    }

    /**
     * @return Builder<MaintenanceOrder>
     */
    private function visibleOrders(): Builder
    {
        $partnerId = ServicePartner::scopeId($this->apiUser());

        return MaintenanceOrder::query()->when(
            $partnerId !== null,
            fn (Builder $query): Builder => $query->whereHas('plan', fn (Builder $plan) => $plan->where('company_id', $partnerId)),
        );
    }

    private function authorizeOrder(string $ability, MaintenanceOrder $order): void
    {
        $this->authorizeApi($ability, $order);

        abort_unless($this->visibleOrders()->whereKey($order->getKey())->exists(), 403);
    }

    private function abortOnDuplicateDueDate(string $planId, Carbon|string $dueAt, ?string $ignoreId = null): void
    {
        $exists = MaintenanceOrder::query()
            ->where('maintenance_plan_id', $planId)
            ->whereDate('due_at', Carbon::parse($dueAt)->toDateString())
            ->when($ignoreId !== null, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->exists();

        abort_if($exists, 409, __('api.errors.maintenance_order_duplicate'));
    }

    private function applyCompletion(MaintenanceOrder $order, Request $request): void
    {
        if (! $order->isDirty('status')) {
            return;
        }

        if ($order->status === MaintenanceOrderStatus::Completed) {
            $order->completed_at ??= now();
            $order->completed_by ??= $request->user()->getKey();

            return;
        }

        $order->completed_at = null;
        $order->completed_by = null;
    }
}
