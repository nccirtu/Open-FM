<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreContractRequest;
use App\Http\Requests\Api\V1\UpdateContractRequest;
use App\Http\Resources\Api\V1\ContractResource;
use App\Models\Contract;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

#[Group('Verträge')]
class ContractController extends ApiController
{
    protected static string $ability = 'contracts';

    public const INCLUDES = ['company', 'properties', 'assets'];

    private const PIVOT_FIELDS = ['property_ids', 'asset_ids'];

    #[Endpoint('Verträge auflisten', 'Paginierte Liste aller Verträge des Mandanten.')]
    #[QueryParameter('filter[search]', description: 'Volltext über Name, Vertragsnummer und Vertragspartner.', type: 'string')]
    #[QueryParameter('filter[status]', description: 'Status: active, terminated, expired; mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[type]', description: 'Art: maintenance, service, rental, insurance, utility, other.', type: 'string')]
    #[QueryParameter('filter[company_id]', description: 'Firmen-ID des Vertragspartners.', type: 'string')]
    #[QueryParameter('filter[property_id]', description: 'Objekt-ID (zugeordnete Objekte), mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[ends_before]', description: 'Vertragsende bis Datum (Y-m-d).', type: 'string', format: 'date')]
    #[QueryParameter('sort', description: 'Sortierfeld, Minus für absteigend: name, end_date, start_date, created_at.', type: 'string', default: 'name')]
    #[QueryParameter('include', description: 'Relationen, durch Komma getrennt: company, properties, assets.', type: 'string')]
    #[QueryParameter('per_page', description: 'Einträge je Seite (1–100).', type: 'integer', default: 25)]
    #[QueryParameter('page', description: 'Seite.', type: 'integer', default: 1)]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeApi('viewAny', Contract::class);

        $contracts = QueryBuilder::for(Contract::class)
            ->allowedFilters(
                AllowedFilter::callback('search', fn (Builder $query, string $value) => $query->where(fn (Builder $inner) => $inner
                    ->where('name', 'like', "%{$value}%")
                    ->orWhere('contract_number', 'like', "%{$value}%")
                    ->orWhere('partner_name', 'like', "%{$value}%"))),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('type'),
                AllowedFilter::exact('company_id'),
                AllowedFilter::callback('property_id', fn (Builder $query, string|array $value) => $query->whereHas('properties', fn (Builder $property) => $property->whereIn('properties.id', (array) $value))),
                AllowedFilter::callback('ends_before', fn (Builder $query, string $value) => $query->whereDate('end_date', '<=', $value)),
            )
            ->allowedSorts('name', 'end_date', 'start_date', 'created_at')
            ->allowedIncludes(...self::INCLUDES)
            ->defaultSort('name');

        return ContractResource::collection($this->paginate($contracts, $request));
    }

    #[Endpoint('Vertrag anzeigen')]
    #[QueryParameter('include', description: 'Relationen, durch Komma getrennt: company, properties, assets.', type: 'string')]
    public function show(Request $request, Contract $contract): ContractResource
    {
        $this->authorizeApi('view', $contract);

        $contract->load($this->requestedIncludes($request, self::INCLUDES));

        return new ContractResource($contract);
    }

    #[Endpoint('Vertrag anlegen', 'Optional property_ids und asset_ids zum Zuordnen von Objekten und Anlagen.')]
    public function store(StoreContractRequest $request): ContractResource
    {
        $validated = $request->validated();

        $contract = new Contract(Arr::except($validated, self::PIVOT_FIELDS));
        $contract->organization_id = $this->organizationId();
        $contract->save();

        $this->syncRelations($contract, $validated);

        return new ContractResource($contract->load(['company', 'properties', 'assets']));
    }

    #[Endpoint('Vertrag ändern', 'property_ids und asset_ids ersetzen die bisherige Zuordnung vollständig, wenn sie übergeben werden.')]
    public function update(UpdateContractRequest $request, Contract $contract): ContractResource
    {
        $validated = $request->validated();

        $contract->fill(Arr::except($validated, self::PIVOT_FIELDS))->save();

        $this->syncRelations($contract, $validated);

        return new ContractResource($contract->load(['company', 'properties', 'assets']));
    }

    #[Endpoint('Vertrag löschen', 'Nur möglich, wenn keine Störungen oder Wartungspläne auf den Vertrag verweisen.')]
    public function destroy(Contract $contract): Response
    {
        $this->authorizeApi('delete', $contract);

        abort_if(
            $contract->tickets()->exists() || $contract->maintenancePlans()->exists(),
            409,
            __('api.errors.contract_in_use'),
        );

        $contract->delete();

        return response()->noContent();
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncRelations(Contract $contract, array $validated): void
    {
        if (array_key_exists('property_ids', $validated)) {
            $contract->properties()->sync($validated['property_ids']);
        }

        if (array_key_exists('asset_ids', $validated)) {
            $contract->assets()->sync($validated['asset_ids']);
        }
    }
}
