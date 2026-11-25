<?php

namespace App\Services\Waste;

use App\Models\Property;
use App\Models\WasteCollection;
use App\Models\WasteSettings;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class HazardousWasteRegister
{
    /**
     * @var array<string, WasteSettings>
     */
    private array $settings = [];

    /**
     * @return Builder<WasteCollection>
     */
    public function query(): Builder
    {
        return WasteCollection::query()->whereHas('fraction', fn (Builder $query) => $query->where('is_hazardous', true));
    }

    public function deadline(WasteCollection $collection): ?CarbonImmutable
    {
        $settings = $this->settings($collection->organization_id);
        $day = CarbonImmutable::parse($collection->collected_at)->startOfDay();

        if (($months = $settings->rule('register_deadline_months')) !== null) {
            return $day->addMonthsNoOverflow((int) $months);
        }

        if (($workingDays = $settings->rule('register_deadline_working_days')) !== null) {
            return $day->addWeekdays((int) $workingDays);
        }

        $days = $settings->rule('register_deadline_days', 10);

        return $days !== null ? $day->addDays((int) $days) : null;
    }

    public function retainUntil(WasteCollection $collection): ?CarbonImmutable
    {
        $years = $this->settings($collection->organization_id)->rule('retention_years', 3);

        return $years !== null ? CarbonImmutable::parse($collection->collected_at)->startOfDay()->addYears((int) $years) : null;
    }

    public function status(WasteCollection $collection, ?CarbonImmutable $today = null): string
    {
        $deadline = $this->deadline($collection);

        return match (true) {
            filled($collection->document_number) => 'entered',
            $deadline !== null && ($today ?? CarbonImmutable::today())->greaterThan($deadline) => 'overdue',
            default => 'open',
        };
    }

    /**
     * @param  array<int, string>|null  $propertyIds
     * @return Collection<int, array{property: Property, mass_t: float, threshold_t: float|null, share: float|null}>
     */
    public function thresholds(int $year, ?array $propertyIds = null): Collection
    {
        $threshold = $this->settings(null)->rule('hazardous_threshold_t', 2);
        $threshold = $threshold !== null ? (float) $threshold : null;

        $masses = $this->query()
            ->when($propertyIds !== null, fn (Builder $query) => $query->whereIn('property_id', $propertyIds))
            ->whereYear('collected_at', $year)
            ->selectRaw('property_id, SUM(mass_kg) as mass')
            ->groupBy('property_id')
            ->pluck('mass', 'property_id');

        return Property::query()
            ->whereIn('id', $masses->keys())
            ->get()
            ->map(fn (Property $property): array => [
                'property' => $property,
                'mass_t' => round((float) $masses[$property->getKey()] / 1000, 3),
                'threshold_t' => $threshold,
                'share' => $threshold > 0 ? (float) $masses[$property->getKey()] / 1000 / $threshold : null,
            ])
            ->sortByDesc('mass_t')
            ->values();
    }

    private function settings(?string $organizationId): WasteSettings
    {
        return $this->settings[$organizationId ?? ''] ??= WasteSettings::forOrganization($organizationId);
    }
}
