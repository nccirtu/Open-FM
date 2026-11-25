<?php

namespace App\Filament\Integrations\MapWidget;

use App\Enums\WastePickupStatus;
use App\Filament\Waste\Pages\ConfirmPickupsPage;
use App\Models\WasteContainer;
use App\Models\WasteFillReading;
use Cafm\MapWidget\MapMarker;
use Cafm\MapWidget\MapWidget;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class WasteContainerMapWidget extends MapWidget
{
    public static int $gridW = 12;

    public static int $gridH = 10;

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', WasteContainer::class) ?? false;
    }

    public function getHeading(): ?string
    {
        return __('waste.widgets.map');
    }

    public function getMarkers(): array
    {
        $since = CarbonImmutable::today()->subDays(14)->toDateString();

        return WasteContainer::query()
            ->active()
            ->with(['property', 'fraction', 'structureNode'])
            ->withCount(['pickups as missed_count' => fn (Builder $query) => $query
                ->where('status', WastePickupStatus::Missed->value)
                ->whereDate('due_on', '>=', $since)])
            ->addSelect(['last_fill_pct' => WasteFillReading::query()
                ->select('fill_level_pct')
                ->whereColumn('waste_container_id', 'waste_containers.id')
                ->latest('measured_at')
                ->limit(1)])
            ->get()
            ->filter(fn (WasteContainer $container): bool => $this->position($container) !== null)
            ->groupBy(fn (WasteContainer $container): string => implode(',', $this->position($container)))
            ->map(fn (Collection $containers): MapMarker => $this->marker($containers))
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, WasteContainer>  $containers
     */
    private function marker(Collection $containers): MapMarker
    {
        /** @var WasteContainer $first */
        $first = $containers->first();
        [$latitude, $longitude] = $this->position($first);

        $color = match (true) {
            $containers->sum('missed_count') > 0 => '#dc2626',
            $containers->contains(fn (WasteContainer $container): bool => (int) $container->getAttribute('last_fill_pct') >= 90) => '#f59e0b',
            default => '#16a34a',
        };

        return MapMarker::make($latitude, $longitude)
            ->label(implode(' › ', array_filter([$first->property?->property_number, $first->structureNode?->name])))
            ->color($color)
            ->url(ConfirmPickupsPage::getUrl(array_filter(['property' => $first->property_id, 'node' => $first->structure_node_id])))
            ->lines($containers->map(fn (WasteContainer $container): string => $container->label()
                .($container->getAttribute('last_fill_pct') !== null ? ' · '.$container->getAttribute('last_fill_pct').' %' : '')
                .($container->missed_count > 0 ? ' · '.__('waste.widgets.map_missed') : ''))
                ->all());
    }

    /**
     * @return array{0: float, 1: float}|null
     */
    private function position(WasteContainer $container): ?array
    {
        if ($container->latitude !== null && $container->longitude !== null) {
            return [round((float) $container->latitude, 5), round((float) $container->longitude, 5)];
        }

        $property = $container->property;

        return $property?->latitude !== null && $property?->longitude !== null
            ? [round((float) $property->latitude, 5), round((float) $property->longitude, 5)]
            : null;
    }
}
