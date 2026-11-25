<?php

namespace App\Support;

use App\Enums\PropertyUsageType;
use App\Models\Property;
use App\Models\WasteGoal;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class WasteFilters
{
    public const EVENT = 'waste-filters-updated';

    public const KIND_OPERATIONS = 'operations';

    public const KIND_CONSTRUCTION = 'construction';

    public const KIND_ALL = 'all';

    public const KINDS = [self::KIND_OPERATIONS, self::KIND_CONSTRUCTION, self::KIND_ALL];

    private function __construct(private EnergyFilters $base, private string $kind = self::KIND_OPERATIONS) {}

    /**
     * @param  array<string, mixed>|null  $filters
     */
    public static function fromArray(?array $filters, ?CarbonImmutable $today = null): self
    {
        $kind = in_array($filters['kind'] ?? null, self::KINDS, true) ? $filters['kind'] : self::KIND_OPERATIONS;

        return new self(EnergyFilters::fromArray($filters, $today), $kind);
    }

    /**
     * @return array<string, string>
     */
    public static function kindOptions(): array
    {
        return collect(self::KINDS)->mapWithKeys(fn (string $kind): array => [$kind => __('waste.filters.kinds.'.$kind)])->all();
    }

    public function kind(): string
    {
        return $this->kind;
    }

    public function withKind(string $kind): self
    {
        return new self($this->base, in_array($kind, self::KINDS, true) ? $kind : self::KIND_OPERATIONS);
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function scopeKind(Builder $query): Builder
    {
        return match ($this->kind) {
            self::KIND_CONSTRUCTION => $query->where('is_construction', true),
            self::KIND_ALL => $query,
            default => $query->where('is_construction', false),
        };
    }

    public static function current(): self
    {
        return self::fromArray(session()->get(self::sessionKey()));
    }

    public static function sessionKey(): string
    {
        return 'waste_dashboard_filters_'.(Filament::getTenant()?->getKey() ?? 'global');
    }

    public function from(): CarbonImmutable
    {
        return $this->base->from;
    }

    public function to(): CarbonImmutable
    {
        return $this->base->to;
    }

    public function propertyId(): ?string
    {
        return $this->base->propertyId;
    }

    public function usageType(): ?PropertyUsageType
    {
        return $this->base->usageType;
    }

    public function label(): string
    {
        return $this->base->label();
    }

    public function year(): int
    {
        return $this->base->year();
    }

    public function includesGoal(WasteGoal $goal): bool
    {
        $ids = $this->base->propertyIds();

        return $ids === null || ($goal->property_id !== null && in_array($goal->property_id, $ids, true));
    }

    public function usesMonths(): bool
    {
        return $this->base->usesMonths();
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function previousYear(): array
    {
        return $this->base->previousYear();
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function scope(Builder $query, string $column = 'property_id'): Builder
    {
        return $this->base->scope($query, $column);
    }

    /**
     * @return Builder<Property>
     */
    public function properties(): Builder
    {
        return $this->base->properties();
    }
}
