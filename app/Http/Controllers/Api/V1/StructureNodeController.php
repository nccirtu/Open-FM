<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreStructureNodeRequest;
use App\Http\Requests\Api\V1\UpdateStructureNodeRequest;
use App\Http\Resources\Api\V1\StructureNodeResource;
use App\Models\Property;
use App\Models\StructureNode;
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
class StructureNodeController extends ApiController
{
    protected static string $ability = 'properties';

    public const INCLUDES = ['property', 'parent', 'children', 'assets'];

    #[Endpoint('Strukturknoten auflisten', 'Paginierte Liste der räumlichen Struktur (Gebäude, Etagen, Einheiten, Räume, Zonen, Außenanlagen) aller Objekte des Mandanten.')]
    #[QueryParameter('filter[search]', description: 'Volltext über Name und IFC-GUID.', type: 'string')]
    #[QueryParameter('filter[property_id]', description: 'Objekt-ID, mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[parent_id]', description: 'ID des übergeordneten Knotens, mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[type]', description: 'Knotentyp: building, floor, unit, room, zone, outdoor.', type: 'string')]
    #[QueryParameter('filter[room_use]', description: 'Raumnutzung: sales_floor, storage, office, technical, sanitary, staff_room, entrance, corridor, outdoor, other.', type: 'string')]
    #[QueryParameter('filter[name]', description: 'Teiltreffer im Namen.', type: 'string')]
    #[QueryParameter('sort', description: 'Sortierfeld, Minus für absteigend: sort, name, created_at.', type: 'string', default: 'sort')]
    #[QueryParameter('include', description: 'Relationen, durch Komma getrennt: property, parent, children, assets.', type: 'string')]
    #[QueryParameter('per_page', description: 'Einträge je Seite (1–100).', type: 'integer', default: 25)]
    #[QueryParameter('page', description: 'Seite.', type: 'integer', default: 1)]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeApi('viewAny', StructureNode::class);

        $nodes = QueryBuilder::for(StructureNode::query()->whereHas('property', fn (Builder $property) => $property->visibleTo($this->apiUser())))
            ->allowedFilters(
                AllowedFilter::callback('search', fn (Builder $query, string $value) => $query->where(fn (Builder $inner) => $inner
                    ->where('name', 'like', "%{$value}%")
                    ->orWhere('ifc_guid', $value))),
                AllowedFilter::exact('property_id'),
                AllowedFilter::exact('parent_id'),
                AllowedFilter::exact('type'),
                AllowedFilter::exact('room_use'),
                AllowedFilter::partial('name'),
            )
            ->allowedSorts('sort', 'name', 'created_at')
            ->allowedIncludes(...self::INCLUDES)
            ->defaultSort('sort', 'name');

        return StructureNodeResource::collection($this->paginate($nodes, $request));
    }

    #[Endpoint('Strukturknoten anzeigen')]
    #[QueryParameter('include', description: 'Relationen, durch Komma getrennt: property, parent, children, assets.', type: 'string')]
    public function show(Request $request, StructureNode $structureNode): StructureNodeResource
    {
        $this->authorizeApi('view', $structureNode);

        $structureNode->load($this->requestedIncludes($request, self::INCLUDES));

        return new StructureNodeResource($structureNode);
    }

    #[Endpoint('Strukturknoten anlegen', 'Der übergeordnete Knoten muss zum selben Objekt gehören.')]
    public function store(StoreStructureNodeRequest $request): StructureNodeResource
    {
        $data = $request->validated();

        $this->authorizeApi('update', Property::query()->findOrFail($data['property_id']));

        $node = (new StructureNode)->forceFill($data);
        $node->organization_id = $this->organizationId();
        $node->save();

        return new StructureNodeResource($node->load('property'));
    }

    #[Endpoint('Strukturknoten ändern')]
    public function update(UpdateStructureNodeRequest $request, StructureNode $structureNode): StructureNodeResource
    {
        $data = $request->validated();

        if (isset($data['property_id'])) {
            $this->authorizeApi('update', Property::query()->findOrFail($data['property_id']));
        }

        $structureNode->forceFill($data)->save();

        return new StructureNodeResource($structureNode->load('property'));
    }

    #[Endpoint('Strukturknoten löschen', 'Nur möglich, wenn der Knoten keine Unterknoten und keine Anlagen hat.')]
    public function destroy(StructureNode $structureNode): Response
    {
        $this->authorizeApi('delete', $structureNode);

        abort_if(
            $structureNode->children()->exists() || $structureNode->assets()->exists(),
            409,
            __('api.errors.structure_node_in_use'),
        );

        $structureNode->delete();

        return response()->noContent();
    }
}
