<?php

namespace App\Filament\Integrations\MapWidget;

use App\Enums\MaintenanceOrderStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Filament\Resources\PropertyResource;
use App\Models\Property;
use Cafm\MapWidget\MapMarker;
use Cafm\MapWidget\MapWidget;
use Illuminate\Database\Eloquent\Builder;

class PropertyMapWidget extends MapWidget
{
    public static int $gridW = 12;

    public static int $gridH = 12;

    protected static ?int $sort = 0;

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Property::class) ?? false;
    }

    public function getHeading(): ?string
    {
        return __('property.map.heading');
    }

    public function getMarkers(): array
    {
        return Property::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->withCount([
                'tickets as open_tickets_count' => fn (Builder $query) => $query
                    ->whereIn('status', TicketStatus::open()),
                'tickets as open_p1_count' => fn (Builder $query) => $query
                    ->whereIn('status', TicketStatus::open())
                    ->where('priority', TicketPriority::P1),
                'maintenanceOrders as overdue_orders_count' => fn (Builder $query) => $query
                    ->where('status', MaintenanceOrderStatus::Open)
                    ->whereDate('due_at', '<', now()->toDateString()),
            ])
            ->get()
            ->map(fn (Property $property): MapMarker => MapMarker::make((float) $property->latitude, (float) $property->longitude)
                ->label(trim($property->property_number.' '.($property->city ?? '')))
                ->color(self::colorFor($property))
                ->url(PropertyResource::getUrl('edit', ['record' => $property]))
                ->lines(array_filter([
                    __('property.map.open_tickets', ['count' => $property->open_tickets_count]),
                    $property->overdue_orders_count > 0
                        ? __('property.map.overdue_orders', ['count' => $property->overdue_orders_count])
                        : null,
                ])))
            ->all();
    }

    public static function colorFor(Property $property): string
    {
        return match (true) {
            $property->overdue_orders_count > 0 || $property->open_p1_count > 0 => '#dc2626',
            $property->open_tickets_count > 0 => '#f59e0b',
            default => '#16a34a',
        };
    }
}
