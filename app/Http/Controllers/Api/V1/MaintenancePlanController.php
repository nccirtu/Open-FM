<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\MaintenanceOrderStatus;
use App\Http\Requests\Api\V1\StoreMaintenancePlanRequest;
use App\Http\Requests\Api\V1\UpdateMaintenancePlanRequest;
use App\Http\Resources\Api\V1\MaintenancePlanResource;
use App\Models\MaintenancePlan;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

#[Group('Wartung')]
class MaintenancePlanController extends ApiController
{
    protected static string $ability = 'maintenance';

    public const INCLUDES = ['asset', 'company', 'inspectionCompany', 'contract', 'orders'];

    #[Endpoint('Wartungspläne auflisten', 'Paginierte Liste aller Wartungs- und Prüfpläne des Mandanten.')]
    #[QueryParameter('filter[search]', description: 'Volltext über Name und Rechtsgrundlage.', type: 'string')]
    #[QueryParameter('filter[asset_id]', description: 'Anlagen-ID, mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[property_id]', description: 'Objekt-ID der Anlage, mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[type]', description: 'Art: maintenance, inspection.', type: 'string')]
    #[QueryParameter('filter[is_active]', description: 'Nur aktive (true) bzw. inaktive (false) Pläne.', type: 'boolean')]
    #[QueryParameter('filter[company_id]', description: 'Firmen-ID des Wartungspartners.', type: 'string')]
    #[QueryParameter('filter[due_before]', description: 'Nächste Fälligkeit bis Datum (Y-m-d).', type: 'string', format: 'date')]
    #[QueryParameter('sort', description: 'Sortierfeld, Minus für absteigend: next_due_at, name, created_at.', type: 'string', default: 'next_due_at')]
    #[QueryParameter('include', description: 'Relationen, durch Komma getrennt: asset, company, inspectionCompany, contract, orders.', type: 'string')]
    #[QueryParameter('per_page', description: 'Einträge je Seite (1–100).', type: 'integer', default: 25)]
    #[QueryParameter('page', description: 'Seite.', type: 'integer', default: 1)]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeApi('viewAny', MaintenancePlan::class);

        $plans = QueryBuilder::for(MaintenancePlan::class)
            ->allowedFilters(
                AllowedFilter::callback('search', fn (Builder $query, string $value) => $query->where(fn (Builder $inner) => $inner
                    ->where('name', 'like', "%{$value}%")
                    ->orWhere('legal_basis', 'like', "%{$value}%"))),
                AllowedFilter::exact('asset_id'),
                AllowedFilter::callback('property_id', fn (Builder $query, string|array $value) => $query->whereHas('asset', fn (Builder $asset) => $asset->whereIn('property_id', (array) $value))),
                AllowedFilter::exact('type'),
                AllowedFilter::exact('is_active'),
                AllowedFilter::exact('company_id'),
                AllowedFilter::callback('due_before', fn (Builder $query, string $value) => $query->whereDate('next_due_at', '<=', $value)),
            )
            ->allowedSorts('next_due_at', 'name', 'created_at')
            ->allowedIncludes(...self::INCLUDES)
            ->defaultSort('next_due_at');

        return MaintenancePlanResource::collection($this->paginate($plans, $request));
    }

    #[Endpoint('Wartungsplan anzeigen')]
    #[QueryParameter('include', description: 'Relationen, durch Komma getrennt: asset, company, inspectionCompany, contract, orders.', type: 'string')]
    public function show(Request $request, MaintenancePlan $maintenancePlan): MaintenancePlanResource
    {
        $this->authorizeApi('view', $maintenancePlan);

        $maintenancePlan->load($this->requestedIncludes($request, self::INCLUDES));

        return new MaintenancePlanResource($maintenancePlan);
    }

    #[Endpoint('Wartungsplan anlegen')]
    public function store(StoreMaintenancePlanRequest $request): MaintenancePlanResource
    {
        $plan = new MaintenancePlan($request->validated());
        $plan->organization_id = $this->organizationId();
        $plan->save();

        return new MaintenancePlanResource($plan->load('asset'));
    }

    #[Endpoint('Wartungsplan ändern')]
    public function update(UpdateMaintenancePlanRequest $request, MaintenancePlan $maintenancePlan): MaintenancePlanResource
    {
        $maintenancePlan->fill($request->validated())->save();

        return new MaintenancePlanResource($maintenancePlan->load('asset'));
    }

    #[Endpoint('Wartungsplan löschen', 'Nur möglich, solange keine abgeschlossenen Aufträge vorliegen; offene Aufträge werden mitgelöscht.')]
    public function destroy(MaintenancePlan $maintenancePlan): Response
    {
        $this->authorizeApi('delete', $maintenancePlan);

        abort_if(
            $maintenancePlan->orders()->where('status', MaintenanceOrderStatus::Completed)->exists(),
            409,
            __('api.errors.maintenance_plan_in_use'),
        );

        $maintenancePlan->delete();

        return response()->noContent();
    }
}
