<?php

namespace App\Filament\Contracts\Widgets;

use App\Enums\ContractType;
use App\Filament\Widgets\BaseChartWidget;
use App\Models\Contract;
use Filament\Support\RawJs;

class ContractsByTypeChartWidget extends BaseChartWidget
{
    public static int $gridW = 6;

    public static int $gridH = 11;

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Contract::class) ?? false;
    }

    public function getHeading(): ?string
    {
        return __('app.widgets.contracts_by_type');
    }

    protected function getData(): array
    {
        $counts = $this->countByEnum(
            Contract::query()->active(),
            'type',
            ContractType::cases(),
        );

        return [
            'labels' => $counts['labels'],
            'datasets' => [
                [
                    'data' => $counts['data'],
                    'backgroundColor' => array_slice(self::PALETTE, 0, count(ContractType::cases())),
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
