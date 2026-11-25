<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\SlaDefinitionResource;
use App\Models\SlaDefinition;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

#[Group('Störungen')]
class SlaDefinitionController extends ApiController
{
    protected static string $ability = 'tickets';

    #[Endpoint('SLA-Definitionen auflisten', 'Reaktions- und Lösungszeiten je Priorität des Mandanten (nur lesend).')]
    #[QueryParameter('filter[priority]', description: 'Priorität: p1, p2, p3; mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[is_active]', description: 'Nur aktive (true) bzw. inaktive (false) Definitionen.', type: 'boolean')]
    #[QueryParameter('sort', description: 'Sortierfeld, Minus für absteigend: priority, response_hours, resolution_hours, created_at.', type: 'string', default: 'priority')]
    #[QueryParameter('per_page', description: 'Einträge je Seite (1–100).', type: 'integer', default: 25)]
    #[QueryParameter('page', description: 'Seite.', type: 'integer', default: 1)]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeApi('viewAny', SlaDefinition::class);

        abort_unless($this->apiUser()->administersOrganization($this->organizationId()), 403);

        $definitions = QueryBuilder::for(SlaDefinition::class)
            ->allowedFilters(
                AllowedFilter::exact('priority'),
                AllowedFilter::exact('is_active'),
            )
            ->allowedSorts('priority', 'response_hours', 'resolution_hours', 'created_at')
            ->defaultSort('priority');

        return SlaDefinitionResource::collection($this->paginate($definitions, $request));
    }

    #[Endpoint('SLA-Definition anzeigen')]
    public function show(SlaDefinition $slaDefinition): SlaDefinitionResource
    {
        $this->authorizeApi('view', $slaDefinition);

        return new SlaDefinitionResource($slaDefinition);
    }
}
