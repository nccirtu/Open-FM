<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreCompanyRequest;
use App\Http\Requests\Api\V1\UpdateCompanyRequest;
use App\Http\Resources\Api\V1\CompanyResource;
use App\Models\Company;
use App\Support\ServicePartner;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

#[Group('Firmen')]
class CompanyController extends ApiController
{
    protected static string $ability = 'companies';

    public const INCLUDES = ['addresses', 'contacts'];

    #[Endpoint('Firmen auflisten', 'Paginierte Liste aller Firmen (Lieferanten und Kunden) des Mandanten.')]
    #[QueryParameter('filter[search]', description: 'Volltext über Name, E-Mail und USt-IdNr.', type: 'string')]
    #[QueryParameter('filter[name]', description: 'Teiltreffer im Namen.', type: 'string')]
    #[QueryParameter('filter[role]', description: 'Rolle: supplier, client, both.', type: 'string')]
    #[QueryParameter('sort', description: 'Sortierfeld, Minus für absteigend: name, created_at.', type: 'string', default: 'name')]
    #[QueryParameter('include', description: 'Relationen, durch Komma getrennt: addresses, contacts.', type: 'string')]
    #[QueryParameter('per_page', description: 'Einträge je Seite (1–100).', type: 'integer', default: 25)]
    #[QueryParameter('page', description: 'Seite.', type: 'integer', default: 1)]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeApi('viewAny', Company::class);

        $companies = QueryBuilder::for(ServicePartner::restrict(Company::query(), $this->apiUser(), 'id'))
            ->allowedFilters(
                AllowedFilter::callback('search', fn (Builder $query, string $value) => $query->where(fn (Builder $inner) => $inner
                    ->where('name', 'like', "%{$value}%")
                    ->orWhere('email', 'like', "%{$value}%")
                    ->orWhere('vat_id', 'like', "%{$value}%"))),
                AllowedFilter::partial('name'),
                AllowedFilter::exact('role'),
            )
            ->allowedSorts('name', 'created_at')
            ->allowedIncludes(...self::INCLUDES)
            ->defaultSort('name');

        return CompanyResource::collection($this->paginate($companies, $request));
    }

    #[Endpoint('Firma anzeigen')]
    #[QueryParameter('include', description: 'Relationen, durch Komma getrennt: addresses, contacts.', type: 'string')]
    public function show(Request $request, Company $company): CompanyResource
    {
        $this->authorizeApi('view', $company);

        $company->load($this->requestedIncludes($request, self::INCLUDES));

        return new CompanyResource($company);
    }

    #[Endpoint('Firma anlegen')]
    public function store(StoreCompanyRequest $request): CompanyResource
    {
        $company = new Company($request->validated());
        $company->organization_id = $this->organizationId();
        $company->save();

        return new CompanyResource($company);
    }

    #[Endpoint('Firma ändern')]
    public function update(UpdateCompanyRequest $request, Company $company): CompanyResource
    {
        $company->fill($request->validated())->save();

        return new CompanyResource($company);
    }

    #[Endpoint('Firma löschen', 'Nur möglich, wenn die Firma nicht mehr von Anlagen, Störungen, Verträgen, Wartungsplänen oder Kostenbuchungen verwendet wird. Adressen und Ansprechpartner werden mitgelöscht.')]
    public function destroy(Company $company): Response
    {
        $this->authorizeApi('delete', $company);

        abort_if(
            $company->assets()->exists()
                || $company->tickets()->exists()
                || $company->contracts()->exists()
                || $company->maintenancePlans()->exists()
                || $company->costEntries()->exists(),
            409,
            __('api.errors.company_in_use'),
        );

        $company->delete();

        return response()->noContent();
    }
}
