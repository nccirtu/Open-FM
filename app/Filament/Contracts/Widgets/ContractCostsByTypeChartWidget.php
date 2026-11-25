<?php

namespace App\Filament\Contracts\Widgets;

use App\Enums\ContractType;
use App\Filament\Widgets\BaseChartWidget;
use App\Models\Contract;
use Filament\Support\RawJs;

class ContractCostsByTypeChartWidget extends BaseChartWidget
{
    public static int $gridW = 12;

    public static int $gridH = 8;

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Contract::class) ?? false;
    }

    public function getHeading(): ?string
    {
        return __('app.widgets.contract_costs_by_type');
    }

    protected function getData(): array
    {
        $sums = Contract::query()->active()->get()
            ->groupBy(fn (Contract $contract): string => $contract->type?->value ?? 'other')
            ->map(fn ($contracts): float => (float) $contracts->sum(fn (Contract $contract): float => $contract->yearlyAmount() ?? 0));

        return [
            'labels' => array_map(fn (ContractType $type): string => $type->getLabel(), ContractType::cases()),
            'datasets' => [
                [
                    'label' => __('app.widgets.contract_costs_series'),
                    'data' => array_map(fn (ContractType $type): float => round($sums[$type->value] ?? 0, 2), ContractType::cases()),
                    'backgroundColor' => self::PALETTE[1],
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
