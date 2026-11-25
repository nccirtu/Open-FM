<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\MeterResource;
use App\Models\Meter;
use App\Services\Energy\EnergyReport;
use Carbon\CarbonImmutable;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

#[Group('Energie')]
class MeterController extends ApiController
{
    protected static string $ability = 'energy';

    public const INCLUDES = ['property', 'latestReading', 'measuredAssets', 'suppliedNodes'];

    public static function middleware(): array
    {
        return [
            new Middleware('ability:energy:read,energy:write', only: ['index', 'show', 'consumption']),
        ];
    }

    #[Endpoint('Zähler auflisten', 'Paginierte Liste aller Zähler bzw. Messstellen des Mandanten.')]
    #[QueryParameter('filter[property_id]', description: 'Objekt-ID, mehrere durch Komma.', type: 'string')]
    #[QueryParameter('filter[medium]', description: 'Energieart: electricity, gas, heat, cold, water, oil, pv_generation.', type: 'string')]
    #[QueryParameter('filter[kind]', description: 'main, sub oder virtual.', type: 'string')]
    #[QueryParameter('filter[number]', description: 'Exakte Zählernummer.', type: 'string')]
    #[QueryParameter('filter[name]', description: 'Teiltreffer im Namen.', type: 'string')]
    #[QueryParameter('sort', description: 'Sortierfeld, Minus für absteigend: name, number, created_at.', type: 'string', default: 'name')]
    #[QueryParameter('include', description: 'Relationen, durch Komma getrennt: property, latestReading, measuredAssets, suppliedNodes.', type: 'string')]
    #[QueryParameter('per_page', description: 'Einträge je Seite (1–100).', type: 'integer', default: 25)]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeApi('viewAny', Meter::class);

        $meters = QueryBuilder::for(Meter::class)
            ->allowedFilters(
                AllowedFilter::exact('property_id'),
                AllowedFilter::exact('medium'),
                AllowedFilter::exact('kind'),
                AllowedFilter::exact('number'),
                AllowedFilter::partial('name'),
            )
            ->allowedSorts('name', 'number', 'created_at')
            ->allowedIncludes(...self::INCLUDES)
            ->defaultSort('name');

        return MeterResource::collection($this->paginate($meters, $request));
    }

    #[Endpoint('Zähler anzeigen')]
    #[QueryParameter('include', description: 'Relationen, durch Komma getrennt: property, latestReading, measuredAssets, suppliedNodes.', type: 'string')]
    public function show(Request $request, Meter $meter): MeterResource
    {
        $this->authorizeApi('view', $meter);

        $meter->load($this->requestedIncludes($request, self::INCLUDES));

        return new MeterResource($meter);
    }

    #[Endpoint('Verbrauch eines Zählers', 'Verbrauch je Tag oder Monat in der Auswertungseinheit (kWh bzw. m³), berechnet aus den Zählerständen.')]
    #[QueryParameter('from', description: 'Erster Tag (Y-m-d), Standard: vor 30 Tagen.', type: 'string', format: 'date')]
    #[QueryParameter('to', description: 'Letzter Tag (Y-m-d), Standard: heute.', type: 'string', format: 'date')]
    #[QueryParameter('interval', description: 'day oder month.', type: 'string', default: 'day')]
    public function consumption(Request $request, Meter $meter, EnergyReport $report): JsonResponse
    {
        $this->authorizeApi('view', $meter);

        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'interval' => ['nullable', 'in:day,month'],
        ]);

        $to = CarbonImmutable::parse($validated['to'] ?? 'today');
        $from = CarbonImmutable::parse($validated['from'] ?? $to->subDays(29));

        abort_if($from->diffInDays($to) > 3660, 422, __('api.errors.range_too_large'));

        if (($validated['interval'] ?? 'day') === 'month') {
            $months = (int) $from->startOfMonth()->diffInMonths($to->startOfMonth()) + 1;
            $values = $report->monthlyTotals([$meter], $from, $months);
        } else {
            $values = $report->dailySeries($meter, $from, $to);
        }

        return response()->json([
            'data' => [
                'meter_id' => $meter->id,
                'unit' => $meter->evaluationUnit(),
                'interval' => $validated['interval'] ?? 'day',
                'values' => collect($values)->map(fn (?float $value, string $period): array => ['period' => $period, 'consumption' => $value === null ? null : round($value, 4)])->values(),
                'total' => round(array_sum(array_filter($values)), 4),
            ],
        ]);
    }
}
