<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\WasteContainerResource;
use App\Models\WasteContainer;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

#[Group('Abfall')]
class WasteContainerController extends ApiController
{
    protected static string $ability = 'waste';

    public const INCLUDES = ['property', 'fraction'];

    #[Endpoint('Abfallbehälter auflisten', 'Behälter mit Fraktion, Volumen und Standort, z. B. zum Abgleich mit Sensoren oder der Tourenplanung des Entsorgers.')]
    #[QueryParameter('filter[property_id]', description: 'Objekt-ID, mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[waste_fraction_id]', description: 'Fraktions-ID, mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[number]', description: 'Exakte Behälternummer.', type: 'string')]
    #[QueryParameter('filter[is_active]', description: '1 oder 0.', type: 'boolean')]
    #[QueryParameter('sort', description: 'number oder created_at, Minus für absteigend.', type: 'string', default: 'number')]
    #[QueryParameter('include', description: 'Relationen, durch Komma getrennt: property, fraction.', type: 'string')]
    #[QueryParameter('per_page', description: 'Einträge je Seite (1–100).', type: 'integer', default: 25)]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeApi('viewAny', WasteContainer::class);

        $containers = QueryBuilder::for(WasteContainer::query()->with('type'))
            ->allowedFilters(
                AllowedFilter::exact('property_id'),
                AllowedFilter::exact('waste_fraction_id'),
                AllowedFilter::exact('number'),
                AllowedFilter::exact('is_active'),
            )
            ->allowedSorts('number', 'created_at')
            ->allowedIncludes(...self::INCLUDES)
            ->defaultSort('number');

        return WasteContainerResource::collection($this->paginate($containers, $request));
    }

    #[Endpoint('Abfallbehälter anzeigen')]
    #[QueryParameter('include', description: 'Relationen, durch Komma getrennt: property, fraction.', type: 'string')]
    public function show(Request $request, WasteContainer $wasteContainer): WasteContainerResource
    {
        $this->authorizeApi('view', $wasteContainer);

        $wasteContainer->load(['type', ...$this->requestedIncludes($request, self::INCLUDES)]);

        return new WasteContainerResource($wasteContainer);
    }
}
