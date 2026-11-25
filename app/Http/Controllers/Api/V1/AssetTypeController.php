<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreAssetTypeRequest;
use App\Http\Requests\Api\V1\UpdateAssetTypeRequest;
use App\Http\Resources\Api\V1\AssetTypeResource;
use App\Models\AssetType;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

#[Group('Anlagen')]
class AssetTypeController extends ApiController
{
    protected static string $ability = 'assets';

    #[Endpoint('Anlagentypen auflisten', 'Anlagentypen sind mandantenübergreifend; die Anzahl der Anlagen bezieht sich auf den Mandanten des Tokens.')]
    #[QueryParameter('filter[name]', description: 'Teiltreffer im Namen.', type: 'string')]
    #[QueryParameter('sort', description: 'Sortierfeld, Minus für absteigend: name, created_at.', type: 'string', default: 'name')]
    #[QueryParameter('per_page', description: 'Einträge je Seite (1–100).', type: 'integer', default: 25)]
    #[QueryParameter('page', description: 'Seite.', type: 'integer', default: 1)]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeApi('viewAny', AssetType::class);

        $types = QueryBuilder::for(AssetType::class)
            ->withCount('assets')
            ->allowedFilters(AllowedFilter::partial('name'))
            ->allowedSorts('name', 'created_at')
            ->defaultSort('name');

        return AssetTypeResource::collection($this->paginate($types, $request));
    }

    #[Endpoint('Anlagentyp anzeigen')]
    public function show(AssetType $assetType): AssetTypeResource
    {
        $this->authorizeApi('view', $assetType);

        return new AssetTypeResource($assetType->loadCount('assets'));
    }

    #[Endpoint('Anlagentyp anlegen')]
    public function store(StoreAssetTypeRequest $request): AssetTypeResource
    {
        return new AssetTypeResource(AssetType::query()->create($request->validated()));
    }

    #[Endpoint('Anlagentyp ändern')]
    public function update(UpdateAssetTypeRequest $request, AssetType $assetType): AssetTypeResource
    {
        $assetType->update($request->validated());

        return new AssetTypeResource($assetType);
    }

    #[Endpoint('Anlagentyp löschen', 'Nur möglich, wenn dem Typ keine Anlagen zugeordnet sind.')]
    public function destroy(AssetType $assetType): Response
    {
        $this->authorizeApi('delete', $assetType);

        abort_if($assetType->assets()->withoutGlobalScopes()->exists(), 409, __('api.errors.asset_type_in_use'));

        $assetType->delete();

        return response()->noContent();
    }
}
