<?php

namespace App\Filament\Fm\Widgets;

use App\Enums\TicketStatus;
use App\Filament\Widgets\BaseChartWidget;
use App\Models\Property;
use App\Models\Ticket;
use Filament\Support\RawJs;

class TicketsPerPropertyChartWidget extends BaseChartWidget
{
    public static int $gridW = 8;

    public static int $gridH = 8;

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Ticket::class) ?? false;
    }

    public function getHeading(): ?string
    {
        return __('app.widgets.tickets_per_property');
    }

    protected function getData(): array
    {
        $top = Property::query()
            ->withCount(['tickets as open_tickets_count' => fn ($query) => $query->whereIn('status', TicketStatus::open())])
            ->orderByDesc('open_tickets_count')
            ->limit(10)
            ->get()
            ->filter(fn (Property $property): bool => $property->open_tickets_count > 0);

        return [
            'labels' => $top->pluck('property_number')->all(),
            'datasets' => [
                [
                    'label' => __('app.widgets.open_tickets_short'),
                    'data' => $top->pluck('open_tickets_count')->all(),
                    'backgroundColor' => self::PALETTE[3],
                ],
            ],
        ];
    }

    protected function getOptions(): array|RawJs|null
    {
        return [
            ...parent::getOptions(),
            'indexAxis' => 'y',
            'plugins' => ['tooltip' => ['enabled' => true], 'legend' => ['display' => false]],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
