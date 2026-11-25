<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\ClassificationNodeResource;
use App\Models\ClassificationNode;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

#[Group('Klassifikation')]
class ClassificationNodeController extends ApiController
{
    protected static string $ability = 'classification';

    public const INCLUDES = ['parent', 'children'];

    #[Endpoint('Klassifikationsknoten auflisten', 'Paginierte Liste des Klassifikationsbaums (z. B. DIN 276) des Mandanten; dient als Kostenstellen und Einordnung der Anlagentypen.')]
    #[QueryParameter('filter[search]', description: 'Volltext über Code und Name.', type: 'string')]
    #[QueryParameter('filter[parent_id]', description: 'ID des übergeordneten Knotens, mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[code]', description: 'Exakter Code.', type: 'string')]
    #[QueryParameter('filter[name]', description: 'Teiltreffer im Namen.', type: 'string')]
    #[QueryParameter('sort', description: 'Sortierfeld, Minus für absteigend: sort, code, name.', type: 'string', default: 'sort')]
    #[QueryParameter('include', description: 'Relationen, durch Komma getrennt: parent, children.', type: 'string')]
    #[QueryParameter('per_page', description: 'Einträge je Seite (1–100).', type: 'integer', default: 25)]
    #[QueryParameter('page', description: 'Seite.', type: 'integer', default: 1)]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeApi('viewAny', ClassificationNode::class);

        $nodes = QueryBuilder::for(ClassificationNode::class)
            ->allowedFilters(
                AllowedFilter::callback('search', fn (Builder $query, string $value) => $query->where(fn (Builder $inner) => $inner
                    ->where('code', 'like', "%{$value}%")
                    ->orWhere('name', 'like', "%{$value}%"))),
                AllowedFilter::exact('parent_id'),
                AllowedFilter::exact('code'),
                AllowedFilter::partial('name'),
            )
            ->allowedSorts('sort', 'code', 'name')
            ->allowedIncludes(...self::INCLUDES)
            ->defaultSort('sort', 'code');

        return ClassificationNodeResource::collection($this->paginate($nodes, $request));
    }

    #[Endpoint('Klassifikationsknoten anzeigen')]
    #[QueryParameter('include', description: 'Relationen, durch Komma getrennt: parent, children.', type: 'string')]
    public function show(Request $request, ClassificationNode $classificationNode): ClassificationNodeResource
    {
        $this->authorizeApi('view', $classificationNode);

        $classificationNode->load($this->requestedIncludes($request, self::INCLUDES));

        return new ClassificationNodeResource($classificationNode);
    }
}
