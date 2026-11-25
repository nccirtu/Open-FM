<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\WasteCollectionSource;
use App\Http\Requests\Api\V1\StoreWasteCollectionsRequest;
use App\Http\Resources\Api\V1\WasteCollectionResource;
use App\Models\Property;
use App\Models\WasteCollection;
use App\Models\WasteContainer;
use App\Models\WasteFraction;
use App\Models\WasteTreatment;
use App\Services\Waste\WasteCollectionRecorder;
use Carbon\CarbonImmutable;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

#[Group('Abfall')]
class WasteCollectionController extends ApiController
{
    protected static string $ability = 'waste';

    #[Endpoint('Leerungen auflisten', 'Leerungen und übergebene Mengen aus allen Quellen (Scan, Erfassung, Import, Schnittstelle, Sensor, laut Plan).')]
    #[QueryParameter('filter[property_id]', description: 'Objekt-ID, mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[waste_container_id]', description: 'Behälter-ID, mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[waste_fraction_id]', description: 'Fraktions-ID, mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[mass_source]', description: 'measured oder estimated.', type: 'string')]
    #[QueryParameter('filter[from]', description: 'Ab Zeitpunkt (Y-m-d oder ISO 8601).', type: 'string')]
    #[QueryParameter('filter[until]', description: 'Bis Zeitpunkt (Y-m-d oder ISO 8601).', type: 'string')]
    #[QueryParameter('sort', description: 'collected_at oder -collected_at.', type: 'string', default: '-collected_at')]
    #[QueryParameter('include', description: 'Relationen: fraction.', type: 'string')]
    #[QueryParameter('per_page', description: 'Einträge je Seite (1–100).', type: 'integer', default: 25)]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeApi('viewAny', WasteCollection::class);

        $collections = QueryBuilder::for(WasteCollection::query()->with('treatment'))
            ->allowedFilters(
                AllowedFilter::exact('property_id'),
                AllowedFilter::exact('waste_container_id'),
                AllowedFilter::exact('waste_fraction_id'),
                AllowedFilter::exact('mass_source'),
                AllowedFilter::callback('from', fn (Builder $query, string $value) => $query->where('collected_at', '>=', CarbonImmutable::parse($value))),
                AllowedFilter::callback('until', fn (Builder $query, string $value) => $query->where('collected_at', '<=', CarbonImmutable::parse($value))),
            )
            ->allowedSorts('collected_at')
            ->allowedIncludes('fraction')
            ->defaultSort('-collected_at');

        return WasteCollectionResource::collection($this->paginate($collections, $request));
    }

    #[Endpoint('Leerungen einspielen', 'Bis zu 1.000 Leerungen je Aufruf, z. B. Wiegedaten des Entsorgers. Am Behälter (container_id oder container_number): mass_kg gewogen oder aus fill_level_pct geschätzt; ein Termin am selben Tag (± 2 Tage) wird damit bestätigt, eine Leerung laut Plan ersetzt. Ohne Behälter: property_id, fraction_id oder fraction_code und mass_kg.')]
    public function store(StoreWasteCollectionsRequest $request, WasteCollectionRecorder $recorder): JsonResponse
    {
        $rows = collect($request->validated('collections'));
        $organizationId = $this->organizationId();

        $containers = WasteContainer::query()
            ->where('organization_id', $organizationId)
            ->where(fn (Builder $query) => $query
                ->whereIn('id', $rows->pluck('container_id')->filter()->unique())
                ->orWhereIn('number', $rows->pluck('container_number')->filter()->unique()))
            ->get();
        $fractions = WasteFraction::query()->where('organization_id', $organizationId)->get();
        $treatments = WasteTreatment::query()->where('organization_id', $organizationId)->pluck('id', 'code');
        $properties = Property::query()->whereIn('id', $rows->pluck('property_id')->filter()->unique())->get()->keyBy('id');

        $collections = DB::transaction(fn () => $rows->map(function (array $row) use ($recorder, $containers, $fractions, $treatments, $properties): WasteCollection {
            $collectedAt = CarbonImmutable::parse($row['collected_at']);
            $attributes = array_filter([
                'waste_treatment_id' => filled($row['treatment_code'] ?? null) ? $treatments[$row['treatment_code']] : null,
                'document_number' => $row['document_number'] ?? null,
                'certificate_number' => $row['certificate_number'] ?? null,
                'note' => $row['note'] ?? null,
            ], fn (mixed $value): bool => $value !== null);

            $container = filled($row['container_id'] ?? null)
                ? $containers->firstWhere('id', $row['container_id'])
                : (filled($row['container_number'] ?? null) ? $containers->firstWhere('number', $row['container_number']) : null);

            if ($container !== null) {
                return $recorder->recordForContainer(
                    $container,
                    $collectedAt,
                    WasteCollectionSource::Api,
                    isset($row['mass_kg']) ? (float) $row['mass_kg'] : null,
                    isset($row['fill_level_pct']) ? (int) $row['fill_level_pct'] : null,
                    $attributes,
                );
            }

            $fraction = filled($row['fraction_id'] ?? null)
                ? $fractions->firstWhere('id', $row['fraction_id'])
                : $fractions->firstWhere('code', $row['fraction_code']);

            return $recorder->recordForProperty(
                $properties[$row['property_id']],
                $fraction,
                $collectedAt,
                (float) $row['mass_kg'],
                WasteCollectionSource::Api,
                $attributes,
            );
        }));

        $collections = (new EloquentCollection($collections->all()))->load('treatment');

        return response()->json([
            'data' => WasteCollectionResource::collection($collections),
            'meta' => [
                'stored' => $collections->count(),
                'confirmed_pickups' => $collections->filter(fn (WasteCollection $collection): bool => $collection->waste_pickup_id !== null)->count(),
            ],
        ], 201);
    }
}
