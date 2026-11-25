<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreWasteFillReadingsRequest;
use App\Http\Resources\Api\V1\WasteFillReadingResource;
use App\Models\WasteContainer;
use App\Services\Waste\WasteSensorService;
use Carbon\CarbonImmutable;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

#[Group('Abfall')]
class WasteFillReadingController extends ApiController
{
    protected static string $ability = 'waste';

    #[Endpoint('Füllstände einspielen', 'Bis zu 1.000 Sensor-Meldungen je Aufruf, Behälter über container_id oder container_number. Eine erkannte Leerung (pickup_detected oder Abfall des Füllstands von ≥ 30 % auf ≤ 10 %) wird mit dem letzten Füllstand gebucht und bestätigt den Termin. Ab der Schwelle aus den Einstellungen wird eine Leerung angefragt, wenn in den nächsten zwei Tagen keine geplant ist.')]
    public function store(StoreWasteFillReadingsRequest $request, WasteSensorService $sensors): JsonResponse
    {
        $rows = collect($request->validated('readings'))->sortBy(fn (array $row): int => CarbonImmutable::parse($row['measured_at'])->getTimestamp())->values();

        $containers = WasteContainer::query()
            ->where('organization_id', $this->organizationId())
            ->where(fn (Builder $query) => $query
                ->whereIn('id', $rows->pluck('container_id')->filter()->unique())
                ->orWhereIn('number', $rows->pluck('container_number')->filter()->unique()))
            ->get();

        $containers->each(fn (WasteContainer $container) => $this->authorizeApi('update', $container));

        $results = DB::transaction(fn () => $rows->map(fn (array $row): array => $sensors->record(
            filled($row['container_id'] ?? null) ? $containers->firstWhere('id', $row['container_id']) : $containers->firstWhere('number', $row['container_number']),
            CarbonImmutable::parse($row['measured_at']),
            (int) $row['fill_level_pct'],
            isset($row['temperature_c']) ? (float) $row['temperature_c'] : null,
            $row['sensor_id'] ?? null,
            (bool) ($row['pickup_detected'] ?? false),
        )));

        return response()->json([
            'data' => WasteFillReadingResource::collection($results->pluck('reading')),
            'meta' => [
                'stored' => $results->count(),
                'pickups_detected' => $results->whereNotNull('collection')->count(),
                'pickups_requested' => $results->whereNotNull('request')->unique(fn (array $result): string => $result['request']->getKey())->count(),
            ],
        ], 201);
    }
}
