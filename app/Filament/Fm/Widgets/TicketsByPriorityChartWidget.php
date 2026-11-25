<?php

namespace App\Filament\Fm\Widgets;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Filament\Widgets\BaseChartWidget;
use App\Models\Ticket;
use App\Support\ServicePartner;
use Filament\Support\RawJs;

class TicketsByPriorityChartWidget extends BaseChartWidget
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
        return __('app.widgets.tickets_by_priority');
    }

    protected function getData(): array
    {
        $counts = $this->countByEnum(
            Ticket::query()->whereIn('status', TicketStatus::open()),
            'priority',
            TicketPriority::cases(),
        );

        return [
            'labels' => $counts['labels'],
            'datasets' => [
                [
                    'data' => $counts['data'],
                    'backgroundColor' => ['#ef4444', '#f59e0b', '#5e81f4'],
                ],
            ],
        ];
    }

    protected function getOptions(): array|RawJs|null
    {
        return $this->doughnutOptions();
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
