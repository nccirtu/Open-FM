@php
    $totals = $this->getTotals();
    $money = fn (float $value): string => number_format($value, 2, ',', '.').' €';
@endphp

<x-filament-panels::page>
    {{ $this->filtersForm }}

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            ['label' => __('waste.invoice.expected'), 'value' => $money($totals['expected'])],
            ['label' => __('waste.invoice.actual'), 'value' => $money($totals['actual'])],
            ['label' => __('waste.invoice.difference'), 'value' => $money($totals['difference'])],
            ['label' => __('waste.invoice.deviations'), 'value' => (string) $totals['deviations']],
        ] as $card)
            <x-filament::section compact>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $card['label'] }}</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-gray-950 dark:text-white">{{ $card['value'] }}</p>
            </x-filament::section>
        @endforeach
    </div>

    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('waste.invoice.help') }}</p>

    {{ $this->table }}
</x-filament-panels::page>
