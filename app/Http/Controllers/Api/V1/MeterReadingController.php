<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\MeterReadingSource;
use App\Enums\MeterReadingStatus;
use App\Http\Requests\Api\V1\StoreMeterReadingsRequest;
use App\Http\Resources\Api\V1\MeterReadingResource;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Services\Energy\MeterReadingRecorder;
use Carbon\CarbonImmutable;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

#[Group('Energie')]
class MeterReadingController extends ApiController
{
    protected static string $ability = 'energy';

    #[Endpoint('Zählerstände auflisten', 'Zählerstände aus allen Quellen (Handy, Import, Schnittstelle).')]
    #[QueryParameter('filter[meter_id]', description: 'Zähler-ID, mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[source]', description: 'manual, import oder api.', type: 'string')]
    #[QueryParameter('filter[status]', description: 'plausible oder suspicious.', type: 'string')]
    #[QueryParameter('filter[from]', description: 'Ab Zeitpunkt (Y-m-d oder ISO 8601).', type: 'string')]
    #[QueryParameter('filter[until]', description: 'Bis Zeitpunkt (Y-m-d oder ISO 8601).', type: 'string')]
    #[QueryParameter('sort', description: 'read_at oder -read_at.', type: 'string', default: '-read_at')]
    #[QueryParameter('per_page', description: 'Einträge je Seite (1–100).', type: 'integer', default: 25)]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeApi('viewAny', MeterReading::class);

        $readings = QueryBuilder::for(MeterReading::class)
            ->allowedFilters(
                AllowedFilter::exact('meter_id'),
                AllowedFilter::exact('source'),
                AllowedFilter::exact('status'),
                AllowedFilter::callback('from', fn (Builder $query, string $value) => $query->where('read_at', '>=', CarbonImmutable::parse($value))),
                AllowedFilter::callback('until', fn (Builder $query, string $value) => $query->where('read_at', '<=', CarbonImmutable::parse($value))),
            )
            ->allowedSorts('read_at')
            ->defaultSort('-read_at');

        return MeterReadingResource::collection($this->paginate($readings, $request));
    }

    #[Endpoint('Zählerstände einspielen', 'Bis zu 1.000 Stände je Aufruf, Zähler über meter_id oder meter_number. Gleicher Zähler und Zeitpunkt überschreibt den vorhandenen Stand. Jeder Stand wird auf Plausibilität geprüft; die Auswertungen aktualisieren sich sofort.')]
    public function store(StoreMeterReadingsRequest $request, MeterReadingRecorder $recorder): JsonResponse
    {
        $rows = $request->validated('readings');
        $numbers = collect($rows)->pluck('meter_number')->filter()->unique()->values();
        $ids = collect($rows)->pluck('meter_id')->filter()->unique()->values();

        $meters = Meter::query()
            ->where('organization_id', $this->organizationId())
            ->where(fn (Builder $query) => $query->whereIn('id', $ids)->orWhereIn('number', $numbers))
            ->get();

        $byId = $meters->keyBy('id');
        $byNumber = $meters->keyBy('number');

        $readings = DB::transaction(fn () => collect($rows)->map(function (array $row) use ($recorder, $byId, $byNumber): MeterReading {
            $meter = filled($row['meter_id'] ?? null) ? $byId[$row['meter_id']] : $byNumber[$row['meter_number']];

            return $recorder->record(
                $meter,
                CarbonImmutable::parse($row['read_at']),
                (float) $row['value'],
                MeterReadingSource::Api,
                ['note' => $row['note'] ?? null],
            );
        }));

        return response()->json([
            'data' => MeterReadingResource::collection($readings),
            'meta' => [
                'stored' => $readings->count(),
                'suspicious' => $readings->filter(fn (MeterReading $reading): bool => $reading->status === MeterReadingStatus::Suspicious)->count(),
            ],
        ], 201);
    }
}
