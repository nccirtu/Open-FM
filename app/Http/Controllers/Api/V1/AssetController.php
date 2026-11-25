<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreAssetRequest;
use App\Http\Requests\Api\V1\UpdateAssetRequest;
use App\Http\Resources\Api\V1\AssetResource;
use App\Models\Asset;
use App\Models\ClassificationNode;
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

#[Group('Anlagen')]
class AssetController extends ApiController
{
    protected static string $ability = 'assets';

    public const INCLUDES = ['property', 'structureNode', 'assetType', 'company', 'inspectionCompany', 'parentAsset', 'childAssets'];

    #[Endpoint('Anlagen auflisten', 'Paginierte Liste aller Anlagen des Mandanten.')]
    #[QueryParameter('filter[search]', description: 'Volltext über Name, Hersteller, Seriennummer und Anlagennummer.', type: 'string')]
    #[QueryParameter('filter[name]', description: 'Teiltreffer im Namen.', type: 'string')]
    #[QueryParameter('filter[asset_number]', description: 'Exakte Anlagennummer.', type: 'integer')]
    #[QueryParameter('filter[manufacturer]', description: 'Teiltreffer im Hersteller.', type: 'string')]
    #[QueryParameter('filter[property_id]', description: 'Objekt-ID, mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[property_number]', description: 'Objektnummer.', type: 'string')]
    #[QueryParameter('filter[structure_node_id]', description: 'Strukturknoten-ID, mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[asset_type_id]', description: 'Anlagentyp-ID, mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[type]', description: 'Name des Anlagentyps.', type: 'string')]
    #[QueryParameter('filter[condition]', description: 'Zustand: good, fair, poor.', type: 'string')]
    #[QueryParameter('filter[service_status]', description: 'Betriebsstatus: in_operation, out_of_operation, in_storage, disposed.', type: 'string')]
    #[QueryParameter('filter[location]', description: 'Strukturknoten-ID inklusive aller untergeordneten Knoten (Gebäude → Etagen → Räume).', type: 'string')]
    #[QueryParameter('filter[classification_node_id]', description: 'Kategorie/Gruppe der Klassifikation (inklusive Unterknoten).', type: 'string')]
    #[QueryParameter('filter[serial_number]', description: 'Teiltreffer in der Seriennummer.', type: 'string')]
    #[QueryParameter('filter[company_id]', description: 'Servicepartner-ID, mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[inspection_company_id]', description: 'Prüffirma-ID, mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[parent_asset_id]', description: 'Übergeordnete Anlage (Unteranlagen dieser Anlage).', type: 'string')]
    #[QueryParameter('filter[top_level]', description: 'true = nur Hauptanlagen, false = nur Unteranlagen.', type: 'boolean')]
    #[QueryParameter('filter[next_maintenance_before]', description: 'Nächste Wartung bis Datum (Y-m-d).', type: 'string', format: 'date')]
    #[QueryParameter('filter[next_maintenance_after]', description: 'Nächste Wartung ab Datum (Y-m-d).', type: 'string', format: 'date')]
    #[QueryParameter('filter[maintenance_overdue]', description: 'true = nächste Wartung liegt in der Vergangenheit.', type: 'boolean')]
    #[QueryParameter('filter[warranty_until_before]', description: 'Garantie endet bis Datum (Y-m-d).', type: 'string', format: 'date')]
    #[QueryParameter('filter[warranty_expired]', description: 'true = Garantie abgelaufen, false = Garantie noch gültig.', type: 'boolean')]
    #[QueryParameter('filter[installed_after]', description: 'Installiert ab Datum (Y-m-d).', type: 'string', format: 'date')]
    #[QueryParameter('filter[installed_before]', description: 'Installiert bis Datum (Y-m-d).', type: 'string', format: 'date')]
    #[QueryParameter('filter[decommissioned]', description: 'true = stillgelegt, false = in Nutzung.', type: 'boolean')]
    #[QueryParameter('filter[bim_linked]', description: 'true = mit BIM-Bauteil verknüpft (ifc_guid gesetzt).', type: 'boolean')]
    #[QueryParameter('sort', description: 'Sortierfeld, Minus für absteigend: name, asset_number, manufacturer, installed_at, next_maintenance_at, warranty_until, created_at, updated_at.', type: 'string', default: '-created_at')]
    #[QueryParameter('include', description: 'Relationen, durch Komma getrennt: property, structureNode, assetType, company, inspectionCompany, parentAsset, childAssets.', type: 'string')]
    #[QueryParameter('per_page', description: 'Einträge je Seite (1–100).', type: 'integer', default: 25)]
    #[QueryParameter('page', description: 'Seite.', type: 'integer', default: 1)]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeApi('viewAny', Asset::class);

        $assets = QueryBuilder::for(Asset::class)
            ->allowedFilters(
                AllowedFilter::callback('search', fn (Builder $query, string $value) => $query->where(fn (Builder $inner) => $inner
                    ->where('name', 'like', "%{$value}%")
                    ->orWhere('manufacturer', 'like', "%{$value}%")
                    ->orWhere('serial_number', 'like', "%{$value}%")
                    ->orWhere('asset_number', $value))),
                AllowedFilter::partial('name'),
                AllowedFilter::exact('asset_number'),
                AllowedFilter::partial('manufacturer'),
                AllowedFilter::exact('property_id'),
                AllowedFilter::callback('property_number', fn (Builder $query, string $value) => $query->whereHas('property', fn (Builder $property) => $property->where('property_number', $value))),
                AllowedFilter::exact('structure_node_id'),
                AllowedFilter::exact('asset_type_id'),
                AllowedFilter::callback('type', fn (Builder $query, string $value) => $query->whereHas('assetType', fn (Builder $type) => $type->where('name', $value))),
                AllowedFilter::exact('condition'),
                AllowedFilter::exact('service_status'),
                AllowedFilter::exact('company_id'),
                AllowedFilter::exact('inspection_company_id'),
                AllowedFilter::exact('parent_asset_id'),
                AllowedFilter::partial('serial_number'),
                AllowedFilter::callback('location', fn (Builder $query, string $value) => $query->whereIn(
                    'structure_node_id',
                    StructureNode::query()->find($value)?->descendantsAndSelf()->pluck('id')->all() ?? [],
                )),
                AllowedFilter::callback('classification_node_id', fn (Builder $query, string $value) => $query->whereHas(
                    'assetType.classifications',
                    fn (Builder $classifications) => $classifications->whereIn('classification_node_id', ClassificationNode::query()->find($value)?->selfAndDescendantIds() ?? []),
                )),
                AllowedFilter::callback('top_level', fn (Builder $query, mixed $value) => self::isTrue($value)
                    ? $query->whereNull('parent_asset_id')
                    : $query->whereNotNull('parent_asset_id')),
                AllowedFilter::callback('next_maintenance_before', fn (Builder $query, string $value) => $query->whereDate('next_maintenance_at', '<=', $value)),
                AllowedFilter::callback('next_maintenance_after', fn (Builder $query, string $value) => $query->whereDate('next_maintenance_at', '>=', $value)),
                AllowedFilter::callback('maintenance_overdue', fn (Builder $query, mixed $value) => self::isTrue($value)
                    ? $query->whereDate('next_maintenance_at', '<', today())
                    : $query->where(fn (Builder $inner) => $inner->whereNull('next_maintenance_at')->orWhereDate('next_maintenance_at', '>=', today()))),
                AllowedFilter::callback('warranty_until_before', fn (Builder $query, string $value) => $query->whereDate('warranty_until', '<=', $value)),
                AllowedFilter::callback('warranty_expired', fn (Builder $query, mixed $value) => self::isTrue($value)
                    ? $query->whereDate('warranty_until', '<', today())
                    : $query->whereDate('warranty_until', '>=', today())),
                AllowedFilter::callback('installed_after', fn (Builder $query, string $value) => $query->whereDate('installed_at', '>=', $value)),
                AllowedFilter::callback('installed_before', fn (Builder $query, string $value) => $query->whereDate('installed_at', '<=', $value)),
                AllowedFilter::callback('decommissioned', fn (Builder $query, mixed $value) => self::isTrue($value)
                    ? $query->whereNotNull('decommissioned_at')
                    : $query->whereNull('decommissioned_at')),
                AllowedFilter::callback('bim_linked', fn (Builder $query, mixed $value) => self::isTrue($value)
                    ? $query->whereNotNull('ifc_guid')
                    : $query->whereNull('ifc_guid')),
            )
            ->allowedSorts('name', 'asset_number', 'manufacturer', 'installed_at', 'next_maintenance_at', 'warranty_until', 'created_at', 'updated_at')
            ->allowedIncludes(...self::INCLUDES)
            ->defaultSort('-created_at');

        return AssetResource::collection($this->paginate($assets, $request));
    }

    #[Endpoint('Anlage anzeigen')]
    #[QueryParameter('include', description: 'Relationen, durch Komma getrennt: property, structureNode, assetType, company, inspectionCompany, parentAsset, childAssets.', type: 'string')]
    public function show(Request $request, Asset $asset): AssetResource
    {
        $this->authorizeApi('view', $asset);

        $asset->load($this->requestedIncludes($request, self::INCLUDES));

        return new AssetResource($asset);
    }

    #[Endpoint('Anlage anlegen')]
    public function store(StoreAssetRequest $request): AssetResource
    {
        $asset = new Asset($request->validated());
        $asset->organization_id = $this->organizationId();
        $asset->save();

        return new AssetResource($asset->load(['property', 'assetType']));
    }

    #[Endpoint('Anlage ändern')]
    public function update(UpdateAssetRequest $request, Asset $asset): AssetResource
    {
        $asset->fill($request->validated())->save();

        return new AssetResource($asset->load(['property', 'assetType']));
    }

    #[Endpoint('Anlage löschen')]
    public function destroy(Asset $asset): Response
    {
        $this->authorizeApi('delete', $asset);

        $asset->delete();

        return response()->noContent();
    }

    private static function isTrue(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOL) || in_array(strtolower((string) $value), ['ja', 'yes'], true);
    }
}
