<?php

namespace App\Services\Waste;

use App\Models\WasteCollection;
use App\Models\WasteDailyValue;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

final class WasteAggregator
{
    public function refresh(string $propertyId, string $fractionId, CarbonInterface $date): void
    {
        $day = CarbonImmutable::parse($date)->startOfDay();

        $collections = WasteCollection::query()
            ->withoutGlobalScopes()
            ->with(['treatment' => fn ($query) => $query->withoutGlobalScopes()])
            ->where('property_id', $propertyId)
            ->where('waste_fraction_id', $fractionId)
            ->whereBetween('collected_at', [$day, $day->endOfDay()])
            ->get();

        WasteDailyValue::query()
            ->withoutGlobalScopes()
            ->where('property_id', $propertyId)
            ->where('waste_fraction_id', $fractionId)
            ->whereDate('date', $day->toDateString())
            ->delete();

        $collections
            ->groupBy(fn (WasteCollection $collection): string => $collection->treatment?->waste_treatment_category_id.'|'.($collection->project_id !== null ? 1 : 0))
            ->each(function ($group, string $key) use ($propertyId, $fractionId, $day): void {
                [$categoryId, $isConstruction] = explode('|', $key);

                $value = new WasteDailyValue([
                    'property_id' => $propertyId,
                    'waste_fraction_id' => $fractionId,
                    'date' => $day->toDateString(),
                    'waste_treatment_category_id' => $categoryId !== '' ? $categoryId : null,
                    'is_construction' => $isConstruction === '1',
                    'mass_kg' => round((float) $group->sum('mass_kg'), 2),
                    'collections' => $group->count(),
                ]);
                $value->organization_id = $group->first()->organization_id;
                $value->save();
            });
    }
}
