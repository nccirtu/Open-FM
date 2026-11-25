<?php

namespace App\Filament\Portal\Widgets;

use App\Enums\TicketStatus;
use App\Filament\Widgets\BaseChartWidget;
use App\Models\Ticket;
use Filament\Support\RawJs;

class ReportsByStatusChartWidget extends BaseChartWidget
{
    public static int $gridW = 4;

    public static int $gridH = 12;

    /** @var array<string, string> */
    private const COLORS = [
        'reported' => '#f59e0b',
        'accepted' => '#0ea5e9',
        'in_progress' => '#6366f1',
        'waiting' => '#fbbf24',
        'resolved' => '#22c55e',
        'closed' => '#94a3b8',
    ];

    public static function canView(): bool
    {
        return auth()->check();
    }

    public function getHeading(): ?string
    {
        return __('portal.widgets.by_status');
    }

    protected function getData(): array
    {
        $counts = $this->countByEnum(Ticket::query()->reportedBy(auth()->user()), 'status', TicketStatus::cases());

        return [
            'labels' => $counts['labels'],
            'datasets' => [
                [
                    'data' => $counts['data'],
                    'backgroundColor' => array_map(fn (TicketStatus $status): string => self::COLORS[$status->value], TicketStatus::cases()),
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
