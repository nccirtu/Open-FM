<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreCompanyAddressRequest;
use App\Http\Requests\Api\V1\UpdateCompanyAddressRequest;
use App\Http\Resources\Api\V1\CompanyAddressResource;
use App\Models\Company;
use App\Models\CompanyAddress;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

#[Group('Firmen')]
class CompanyAddressController extends ApiController
{
    protected static string $ability = 'companies';

    #[Endpoint('Adressen einer Firma auflisten')]
    #[QueryParameter('filter[type]', description: 'Adressart: billing, shipping, site, other.', type: 'string')]
    #[QueryParameter('filter[city]', description: 'Teiltreffer im Ort.', type: 'string')]
    #[QueryParameter('filter[is_primary]', description: 'Nur Hauptadresse (true/false).', type: 'boolean')]
    #[QueryParameter('sort', description: 'Sortierfeld, Minus für absteigend: is_primary, type, city, created_at.', type: 'string', default: '-is_primary')]
    #[QueryParameter('per_page', description: 'Einträge je Seite (1–100).', type: 'integer', default: 25)]
    #[QueryParameter('page', description: 'Seite.', type: 'integer', default: 1)]
    public function index(Request $request, Company $company): AnonymousResourceCollection
    {
        $this->authorizeApi('view', $company);

        $addresses = QueryBuilder::for($company->addresses()->getQuery()->reorder())
            ->allowedFilters(
                AllowedFilter::exact('type'),
                AllowedFilter::partial('city'),
                AllowedFilter::exact('is_primary'),
            )
            ->allowedSorts('is_primary', 'type', 'city', 'created_at')
            ->defaultSort('-is_primary', 'created_at');

        return CompanyAddressResource::collection($this->paginate($addresses, $request));
    }

    #[Endpoint('Adresse anzeigen')]
    public function show(CompanyAddress $address): CompanyAddressResource
    {
        $this->authorizeTenant($address);
        $this->authorizeApi('view', $address);

        return new CompanyAddressResource($address);
    }

    #[Endpoint('Adresse anlegen', 'Wird is_primary gesetzt, verlieren die übrigen Adressen der Firma diese Markierung.')]
    public function store(StoreCompanyAddressRequest $request, Company $company): CompanyAddressResource
    {
        $this->authorizeApi('update', $company);

        $address = $company->addresses()->create($request->validated());

        return new CompanyAddressResource($address);
    }

    #[Endpoint('Adresse ändern')]
    public function update(UpdateCompanyAddressRequest $request, CompanyAddress $address): CompanyAddressResource
    {
        $this->authorizeTenant($address);

        $address->fill($request->validated())->save();

        return new CompanyAddressResource($address);
    }

    #[Endpoint('Adresse löschen')]
    public function destroy(CompanyAddress $address): Response
    {
        $this->authorizeTenant($address);
        $this->authorizeApi('delete', $address);

        $address->delete();

        return response()->noContent();
    }

    private function authorizeTenant(CompanyAddress $address): void
    {
        abort_if($address->company === null, 404);
    }
}
