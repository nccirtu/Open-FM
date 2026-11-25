<?php

namespace App\Services\Waste;

use App\Models\Project;
use App\Models\WasteCollection;
use App\Models\WasteSettings;

final class ConstructionWasteSummary
{
    public const EXCLUDED_CODES = ['17 05 04'];

    /**
     * @return array{mass_t: float, base_t: float, recovered_t: float, recovery_rate: float|null, target: float|null, met: bool|null, volume_m3: float, small_quantity: bool, small_quantity_m3: float|null, small_quantity_t: float|null, construction_year: int|null, asbestos_risk: bool, asbestos_year: int|null}
     */
    public function for(Project $project): array
    {
        $settings = WasteSettings::forOrganization($project->organization_id);
        $collections = WasteCollection::query()
            ->withoutGlobalScopes()
            ->where('project_id', $project->getKey())
            ->with(['fraction', 'treatment.category'])
            ->get();

        $base = $collections->filter(fn (WasteCollection $collection): bool => ! ($collection->fraction?->is_hazardous ?? false)
            && ! in_array($collection->fraction?->code, self::EXCLUDED_CODES, true));
        $recovered = $base->filter(fn (WasteCollection $collection): bool => (bool) $collection->treatment?->category?->counts_as_recycling);

        $baseTonnes = (float) $base->sum('mass_kg') / 1000;
        $rate = $baseTonnes > 0 ? (float) $recovered->sum('mass_kg') / 1000 / $baseTonnes * 100 : null;
        $target = $settings->rule('construction_recovery_pct', 70);
        $target = $target !== null ? (float) $target : null;
        $volume = (float) $collections->sum('volume_m3');
        $thresholdTonnes = $settings->rule('construction_small_quantity_t');
        $smallQuantity = $thresholdTonnes === null ? $settings->rule('construction_small_quantity_m3', 10) : null;
        $asbestosYear = $settings->rule('asbestos_ban_year', 1993);
        $constructionYear = $project->property?->construction_year;

        return [
            'mass_t' => round((float) $collections->sum('mass_kg') / 1000, 3),
            'base_t' => round($baseTonnes, 3),
            'recovered_t' => round((float) $recovered->sum('mass_kg') / 1000, 3),
            'recovery_rate' => $rate,
            'target' => $target,
            'met' => $rate !== null && $target !== null ? $rate >= $target : null,
            'volume_m3' => round($volume, 1),
            'small_quantity' => match (true) {
                $thresholdTonnes !== null => (float) $collections->sum('mass_kg') / 1000 <= (float) $thresholdTonnes,
                $smallQuantity !== null => $volume <= (float) $smallQuantity,
                default => false,
            },
            'small_quantity_m3' => $smallQuantity !== null ? (float) $smallQuantity : null,
            'small_quantity_t' => $thresholdTonnes !== null ? (float) $thresholdTonnes : null,
            'construction_year' => $constructionYear,
            'asbestos_risk' => $constructionYear !== null && $asbestosYear !== null && $constructionYear < (int) $asbestosYear,
            'asbestos_year' => $asbestosYear !== null ? (int) $asbestosYear : null,
        ];
    }
}
