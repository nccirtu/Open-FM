<?php

namespace App\Filament\Portal\Widgets;

use App\Enums\TicketCategory;
use App\Enums\TicketStatus;
use App\Filament\Widgets\BaseChartWidget;
use App\Models\Ticket;
use Filament\Support\RawJs;

class ReportsByCategoryChartWidget extends BaseChartWidget
{
    public static int $gridW = 4;

    public static int $gridH = 10;

    public static function canView(): bool
    {
        return auth()->check();
    }

    public function getHeading(): ?string
    {
        return __('portal.widgets.by_category');
    }

    protected function getData(): array
    {
        $counts = $this->countByEnum(
            Ticket::query()->reportedBy(auth()->user())->whereIn('status', TicketStatus::open()),
            'category',
            TicketCategory::cases(),
        );

        return [
            'labels' => $counts['labels'],
            'datasets' => [
                [
                    'label' => __('portal.widgets.open_count'),
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
            'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
