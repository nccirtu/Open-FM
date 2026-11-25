<?php

namespace App\Support;

use App\Enums\PropertyUsageType;
use App\Models\EnergyGoal;
use App\Models\Property;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class EnergyFilters
{
    public const EVENT = 'energy-filters-updated';

    public const PERIODS = ['this_month', 'last_month', 'last_30_days', 'this_quarter', 'this_year', 'last_year', 'last_12_months', 'custom'];

    public const DEFAULT_PERIOD = 'this_year';

    /**
     * @param  array<int, string>|null  $propertyIds
     */
    private function __construct(
        public readonly string $period,
        public readonly CarbonImmutable $from,
        public readonly CarbonImmutable $to,
        public readonly ?string $propertyId,
        public readonly ?PropertyUsageType $usageType,
        private ?array $propertyIds = null,
        private bool $resolvedPropertyIds = false,
    ) {}

    /**
     * @param  array<string, mixed>|null  $filters
     */
    public static function fromArray(?array $filters, ?CarbonImmutable $today = null): self
    {
        $today = ($today ?? CarbonImmutable::today())->startOfDay();
        $period = in_array($filters['period'] ?? null, self::PERIODS, true) ? $filters['period'] : self::DEFAULT_PERIOD;

        [$from, $to] = match ($period) {
            'this_month' => [$today->startOfMonth(), $today],
            'last_month' => [$today->subMonthNoOverflow()->startOfMonth(), $today->subMonthNoOverflow()->endOfMonth()->startOfDay()],
            'last_30_days' => [$today->subDays(29), $today],
            'this_quarter' => [$today->startOfQuarter(), $today],
            'last_year' => [$today->subYear()->startOfYear(), $today->subYear()->endOfYear()->startOfDay()],
            'last_12_months' => [$today->startOfMonth()->subMonths(11), $today],
            'custom' => [self::date($filters['from'] ?? null) ?? $today->startOfYear(), self::date($filters['until'] ?? null) ?? $today],
            default => [$today->startOfYear(), $today],
        };

        if ($to->greaterThan($today)) {
            $to = $today;
        }

        if ($from->greaterThan($to)) {
            $from = $to;
        }

        return new self(
            period: $period,
            from: $from,
            to: $to,
            propertyId: filled($filters['property_id'] ?? null) ? (string) $filters['property_id'] : null,
            usageType: ($filters['usage_type'] ?? null) instanceof PropertyUsageType ? $filters['usage_type'] : PropertyUsageType::tryFrom((string) ($filters['usage_type'] ?? '')),
        );
    }

    public static function current(): self
    {
        return self::fromArray(session()->get(self::sessionKey()));
    }

    public static function sessionKey(): string
    {
        return 'energy_dashboard_filters_'.(Filament::getTenant()?->getKey() ?? 'global');
    }

    /**
     * @return array<string, string>
     */
    public static function periodOptions(): array
    {
        return collect(self::PERIODS)->mapWithKeys(fn (string $period): array => [$period => __('energy.filters.periods.'.$period)])->all();
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function previousYear(): array
    {
        return [$this->from->subYearNoOverflow(), $this->to->subYearNoOverflow()];
    }

    public function year(): int
    {
        return $this->to->year;
    }

    public function usesMonths(): bool
    {
        return $this->from->diffInDays($this->to) > 62;
    }

    public function label(): string
    {
        return $this->from->format('d.m.Y').' – '.$this->to->format('d.m.Y');
    }

    public function isWholeOrganization(): bool
    {
        return $this->propertyId === null && $this->usageType === null;
    }

    /**
     * @return array<int, string>|null
     */
    public function propertyIds(): ?array
    {
        if (! $this->resolvedPropertyIds) {
            $this->resolvedPropertyIds = true;
            $this->propertyIds = match (true) {
                $this->propertyId !== null => [$this->propertyId],
                $this->usageType !== null => Property::query()->where('usage_type', $this->usageType->value)->pluck('id')->all(),
                default => null,
            };
        }

        return $this->propertyIds;
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function scope(Builder $query, string $column = 'property_id'): Builder
    {
        $ids = $this->propertyIds();

        return $ids === null ? $query : $query->whereIn($column, $ids);
    }

    /**
     * @return Builder<Property>
     */
    public function properties(): Builder
    {
        return $this->scope(Property::query(), 'properties.id');
    }

    public function includesGoal(EnergyGoal $goal): bool
    {
        $ids = $this->propertyIds();

        if ($ids === null) {
            return true;
        }

        $subject = $goal->goalable;
        $propertyId = $subject instanceof Property ? $subject->getKey() : $subject?->getAttribute('property_id');

        return $propertyId !== null && in_array($propertyId, $ids, true);
    }

    private static function date(mixed $value): ?CarbonImmutable
    {
        if (blank($value)) {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
