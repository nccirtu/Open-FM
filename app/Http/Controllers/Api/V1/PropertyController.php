<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StorePropertyRequest;
use App\Http\Requests\Api\V1\UpdatePropertyRequest;
use App\Http\Resources\Api\V1\PropertyResource;
use App\Models\Property;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

#[Group('Objekte')]
class PropertyController extends ApiController
{
    protected static string $ability = 'properties';

    public const INCLUDES = ['manager', 'structureNodes', 'assets'];

    #[Endpoint('Objekte auflisten', 'Paginierte Liste aller Objekte des Mandanten.')]
    #[QueryParameter('filter[search]', description: 'Volltext über Objektnummer, Straße und Ort.', type: 'string')]
    #[QueryParameter('filter[property_number]', description: 'Exakte Objektnummer.', type: 'string')]
    #[QueryParameter('filter[city]', description: 'Teiltreffer im Ort.', type: 'string')]
    #[QueryParameter('filter[country]', description: 'Exaktes Land.', type: 'string')]
    #[QueryParameter('filter[energy_rating]', description: 'Energieeffizienzklasse: A–G, mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[manager_id]', description: 'Betreuer-ID (Benutzer), mehrere durch Komma.', type: 'string')]
    #[QueryParameter('sort', description: 'Sortierfeld, Minus für absteigend: property_number, city, created_at, updated_at.', type: 'string', default: 'property_number')]
    #[QueryParameter('include', description: 'Relationen, durch Komma getrennt: manager, structureNodes, assets.', type: 'string')]
    #[QueryParameter('per_page', description: 'Einträge je Seite (1–100).', type: 'integer', default: 25)]
    #[QueryParameter('page', description: 'Seite.', type: 'integer', default: 1)]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeApi('viewAny', Property::class);

        $properties = QueryBuilder::for(Property::query()->visibleTo($this->apiUser()))
            ->allowedFilters(
                AllowedFilter::callback('search', fn (Builder $query, string $value) => $query->where(fn (Builder $inner) => $inner
                    ->where('property_number', 'like', "%{$value}%")
                    ->orWhere('street', 'like', "%{$value}%")
                    ->orWhere('city', 'like', "%{$value}%"))),
                AllowedFilter::exact('property_number'),
                AllowedFilter::partial('city'),
                AllowedFilter::exact('country'),
                AllowedFilter::exact('energy_rating'),
                AllowedFilter::exact('manager_id'),
            )
            ->allowedSorts('property_number', 'city', 'created_at', 'updated_at')
            ->allowedIncludes(...self::INCLUDES)
            ->defaultSort('property_number');

        return PropertyResource::collection($this->paginate($properties, $request));
    }

    #[Endpoint('Objekt anzeigen')]
    #[QueryParameter('include', description: 'Relationen, durch Komma getrennt: manager, structureNodes, assets.', type: 'string')]
    public function show(Request $request, Property $property): PropertyResource
    {
        $this->authorizeApi('view', $property);

        $property->load($this->requestedIncludes($request, self::INCLUDES));

        return new PropertyResource($property);
    }

    #[Endpoint('Objekt anlegen')]
    public function store(StorePropertyRequest $request): PropertyResource
    {
        $property = new Property($this->enforceManagerAssignment($request->validated(), Property::class));
        $property->organization_id = $this->organizationId();
        $property->save();

        return new PropertyResource($property);
    }

    #[Endpoint('Objekt ändern')]
    public function update(UpdatePropertyRequest $request, Property $property): PropertyResource
    {
        $property->fill($this->enforceManagerAssignment($request->validated(), $property))->save();

        return new PropertyResource($property);
    }

    #[Endpoint('Objekt löschen', 'Nur möglich, wenn dem Objekt keine Anlagen, Störungen oder Projekte zugeordnet sind.')]
    public function destroy(Property $property): Response
    {
        $this->authorizeApi('delete', $property);

        abort_if(
            $property->assets()->exists() || $property->tickets()->exists() || $property->projects()->exists(),
            409,
            __('api.errors.property_in_use'),
        );

        $property->delete();

        return response()->noContent();
    }
}
