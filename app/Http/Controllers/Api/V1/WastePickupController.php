<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\WastePickupResource;
use App\Models\WastePickup;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

#[Group('Abfall')]
class WastePickupController extends ApiController
{
    protected static string $ability = 'waste';

    public const INCLUDES = ['container', 'collection'];

    #[Endpoint('Abfuhrtermine auflisten', 'Geplante, angefragte, erledigte, ausgefallene und abgesagte Termine. Nach der Stichzeit gilt ein geplanter Termin laut Plan als erledigt (confirmed_via: schedule); eingespielte Leerungen ersetzen die geschätzte Menge.')]
    #[QueryParameter('filter[property_id]', description: 'Objekt-ID, mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[waste_container_id]', description: 'Behälter-ID, mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[status]', description: 'requested, planned, confirmed, missed oder cancelled; mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[from]', description: 'Fällig ab (Y-m-d).', type: 'string', format: 'date')]
    #[QueryParameter('filter[until]', description: 'Fällig bis (Y-m-d).', type: 'string', format: 'date')]
    #[QueryParameter('sort', description: 'due_on oder -due_on.', type: 'string', default: 'due_on')]
    #[QueryParameter('include', description: 'Relationen, durch Komma getrennt: container, collection.', type: 'string')]
    #[QueryParameter('per_page', description: 'Einträge je Seite (1–100).', type: 'integer', default: 25)]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeApi('viewAny', WastePickup::class);

        $pickups = QueryBuilder::for(WastePickup::class)
            ->allowedFilters(
                AllowedFilter::exact('property_id'),
                AllowedFilter::exact('waste_container_id'),
                AllowedFilter::exact('status'),
                AllowedFilter::callback('from', fn (Builder $query, string $value) => $query->whereDate('due_on', '>=', $value)),
                AllowedFilter::callback('until', fn (Builder $query, string $value) => $query->whereDate('due_on', '<=', $value)),
            )
            ->allowedSorts('due_on')
            ->allowedIncludes(...self::INCLUDES)
            ->defaultSort('due_on');

        return WastePickupResource::collection($this->paginate($pickups, $request));
    }
}
