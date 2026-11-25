<?php

namespace App\Filament\Fm\Widgets;

use App\Enums\TicketCategory;
use App\Enums\TicketStatus;
use App\Filament\Widgets\BaseChartWidget;
use App\Models\Ticket;
use App\Support\ServicePartner;
use Filament\Support\RawJs;

class TicketsByCategoryChartWidget extends BaseChartWidget
{
    public static int $gridW = 4;

    public static int $gridH = 8;

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user !== null && ! ServicePartner::isExternal($user) && $user->can('viewAny', Ticket::class);
    }

    public function getHeading(): ?string
    {
        return __('app.widgets.tickets_by_category');
    }

    protected function getData(): array
    {
        $counts = $this->countByEnum(
            Ticket::query()->whereIn('status', TicketStatus::open()),
            'category',
            TicketCategory::cases(),
        );

        return [
            'labels' => $counts['labels'],
            'datasets' => [
                [
                    'label' => __('app.widgets.open_tickets_short'),
                    'data' => $counts['data'],
                    'backgroundColor' => self::PALETTE[0],
                ],
            ],
        ];
    }

    protected function getOptions(): array|RawJs|null
    {
        return [
            ...parent::getOptions(),
            'plugins' => ['tooltip' => ['enabled' => true], 'legend' => ['display' => false]],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
