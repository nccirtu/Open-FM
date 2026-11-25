<?php

namespace App\Services\Waste;

use App\Enums\WasteEsrsCategory;
use App\Models\Organization;
use App\Models\WasteContainerType;
use App\Models\WasteFractionGroup;
use App\Models\WasteTreatment;
use App\Models\WasteTreatmentCategory;
use Illuminate\Database\Eloquent\Model;

class WasteLookupDefaults
{
    /**
     * @var array<string, array{0: bool, 1: bool, 2: string}>
     */
    public const TREATMENT_CATEGORIES = [
        'reuse' => [true, true, '#047857'],
        'recycling' => [true, true, '#059669'],
        'other_recovery' => [true, false, '#0284c7'],
        'incineration' => [false, false, '#e11d48'],
        'landfill' => [false, false, '#9f1239'],
        'other_disposal' => [false, false, '#be123c'],
    ];

    /**
     * @var array<string, string>
     */
    public const TREATMENTS = [
        'reuse' => 'reuse',
        'R1' => 'other_recovery', 'R2' => 'recycling', 'R3' => 'recycling', 'R4' => 'recycling',
        'R5' => 'recycling', 'R6' => 'recycling', 'R7' => 'recycling', 'R8' => 'recycling',
        'R9' => 'recycling', 'R10' => 'other_recovery', 'R11' => 'other_recovery',
        'R12' => 'other_recovery', 'R13' => 'other_recovery',
        'D1' => 'landfill', 'D2' => 'other_disposal', 'D3' => 'other_disposal', 'D4' => 'other_disposal',
        'D5' => 'landfill', 'D6' => 'other_disposal', 'D7' => 'other_disposal', 'D8' => 'other_disposal',
        'D9' => 'other_disposal', 'D10' => 'incineration', 'D11' => 'incineration', 'D12' => 'landfill',
        'D13' => 'other_disposal', 'D14' => 'other_disposal', 'D15' => 'other_disposal',
    ];

    /**
     * @var array<string, bool>
     */
    public const FRACTION_GROUPS = [
        'paper' => true,
        'glass' => true,
        'plastics' => true,
        'metals' => true,
        'wood' => true,
        'textiles' => true,
        'bio' => true,
        'packaging' => true,
        'electrical' => true,
        'hazardous' => true,
        'construction' => true,
        'mixed' => false,
        'other' => true,
    ];

    /**
     * @var array<string, int|null>
     */
    public const CONTAINER_TYPES = [
        'bin' => null,
        'skip' => 7000,
        'roll_off' => 30000,
        'compactor' => 20000,
        'collection_box' => 60,
        'sack' => 120,
        'other' => null,
    ];

    public function seed(Organization $organization): void
    {
        $locale = $organization->default_locale ?: config('app.locale');
        $sort = 0;
        $categoryIds = [];

        foreach (self::TREATMENT_CATEGORIES as $key => [$isRecovery, $countsAsRecycling, $color]) {
            $categoryIds[$key] = $this->firstOrCreate(WasteTreatmentCategory::class, $organization, ['key' => $key], [
                'name' => __("waste.defaults.treatment_categories.{$key}", locale: $locale),
                'is_recovery' => $isRecovery,
                'counts_as_recycling' => $countsAsRecycling,
                'esrs_category' => WasteEsrsCategory::from($key),
                'color' => $color,
                'sort' => $sort++,
            ])->getKey();
        }

        $sort = 0;

        foreach (self::TREATMENTS as $code => $category) {
            $this->firstOrCreate(WasteTreatment::class, $organization, ['code' => $code === 'reuse' ? null : $code, 'name' => $code === 'reuse' ? __('waste.defaults.treatments.reuse', locale: $locale) : null], [
                'name' => __("waste.defaults.treatments.{$code}", locale: $locale),
                'waste_treatment_category_id' => $categoryIds[$category] ?? null,
                'sort' => $sort++,
            ]);
        }

        $sort = 0;

        foreach (self::FRACTION_GROUPS as $key => $countsAsSeparated) {
            $this->firstOrCreate(WasteFractionGroup::class, $organization, ['key' => $key], [
                'name' => __("waste.defaults.fraction_groups.{$key}", locale: $locale),
                'counts_as_separated' => $countsAsSeparated,
                'sort' => $sort++,
            ]);
        }

        $sort = 0;

        foreach (self::CONTAINER_TYPES as $key => $volume) {
            $this->firstOrCreate(WasteContainerType::class, $organization, ['key' => $key], [
                'name' => __("waste.defaults.container_types.{$key}", locale: $locale),
                'default_volume_l' => $volume,
                'sort' => $sort++,
            ]);
        }
    }

    /**
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $model
     * @param  array<string, mixed>  $match
     * @param  array<string, mixed>  $values
     */
    private function firstOrCreate(string $model, Organization $organization, array $match, array $values): Model
    {
        $match = array_filter($match, fn (mixed $value): bool => $value !== null);

        $existing = $model::query()
            ->withoutGlobalScopes()
            ->where('organization_id', $organization->getKey())
            ->where($match)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $record = new $model([...$match, ...$values]);
        $record->organization_id = $organization->getKey();
        $record->save();

        return $record;
    }
}
