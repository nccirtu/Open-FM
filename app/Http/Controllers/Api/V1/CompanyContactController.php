<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreCompanyContactRequest;
use App\Http\Requests\Api\V1\UpdateCompanyContactRequest;
use App\Http\Resources\Api\V1\CompanyContactResource;
use App\Models\Company;
use App\Models\CompanyContact;
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
class CompanyContactController extends ApiController
{
    protected static string $ability = 'companies';

    #[Endpoint('Ansprechpartner einer Firma auflisten')]
    #[QueryParameter('filter[search]', description: 'Volltext über Vorname, Nachname und E-Mail.', type: 'string')]
    #[QueryParameter('filter[last_name]', description: 'Teiltreffer im Nachnamen.', type: 'string')]
    #[QueryParameter('filter[is_primary]', description: 'Nur Hauptansprechpartner (true/false).', type: 'boolean')]
    #[QueryParameter('sort', description: 'Sortierfeld, Minus für absteigend: is_primary, last_name, first_name, created_at.', type: 'string', default: '-is_primary')]
    #[QueryParameter('per_page', description: 'Einträge je Seite (1–100).', type: 'integer', default: 25)]
    #[QueryParameter('page', description: 'Seite.', type: 'integer', default: 1)]
    public function index(Request $request, Company $company): AnonymousResourceCollection
    {
        $this->authorizeApi('view', $company);

        $contacts = QueryBuilder::for($company->contacts()->getQuery()->reorder())
            ->allowedFilters(
                AllowedFilter::callback('search', fn (Builder $query, string $value) => $query->where(fn (Builder $inner) => $inner
                    ->where('first_name', 'like', "%{$value}%")
                    ->orWhere('last_name', 'like', "%{$value}%")
                    ->orWhere('email', 'like', "%{$value}%"))),
                AllowedFilter::partial('last_name'),
                AllowedFilter::exact('is_primary'),
            )
            ->allowedSorts('is_primary', 'last_name', 'first_name', 'created_at')
            ->defaultSort('-is_primary', 'last_name');

        return CompanyContactResource::collection($this->paginate($contacts, $request));
    }

    #[Endpoint('Ansprechpartner anzeigen')]
    public function show(CompanyContact $contact): CompanyContactResource
    {
        $this->authorizeTenant($contact);
        $this->authorizeApi('view', $contact);

        return new CompanyContactResource($contact);
    }

    #[Endpoint('Ansprechpartner anlegen', 'Wird is_primary gesetzt, verlieren die übrigen Ansprechpartner der Firma diese Markierung.')]
    public function store(StoreCompanyContactRequest $request, Company $company): CompanyContactResource
    {
        $this->authorizeApi('update', $company);

        $contact = $company->contacts()->create($request->validated());

        return new CompanyContactResource($contact);
    }

    #[Endpoint('Ansprechpartner ändern')]
    public function update(UpdateCompanyContactRequest $request, CompanyContact $contact): CompanyContactResource
    {
        $this->authorizeTenant($contact);

        $contact->fill($request->validated())->save();

        return new CompanyContactResource($contact);
    }

    #[Endpoint('Ansprechpartner löschen')]
    public function destroy(CompanyContact $contact): Response
    {
        $this->authorizeTenant($contact);
        $this->authorizeApi('delete', $contact);

        $contact->delete();

        return response()->noContent();
    }

    private function authorizeTenant(CompanyContact $contact): void
    {
        abort_if($contact->company === null, 404);
    }
}
