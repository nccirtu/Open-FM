@php
    $allocation = $this->getAllocation();
@endphp

<x-filament-panels::page>
    {{ $this->filtersForm }}

    @if ($allocation)
        <div class="grid gap-4 sm:grid-cols-2">
            <x-filament::section compact>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('waste.allocation.total', ['year' => $this->year]) }}</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-gray-950 dark:text-white">{{ number_format($allocation['total'], 2, ',', '.') }} €</p>
            </x-filament::section>
            <x-filament::section compact>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('waste.allocation.total_area') }}</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-gray-950 dark:text-white">{{ number_format($allocation['area'], 2, ',', '.') }} m²</p>
            </x-filament::section>
        </div>
    @endif

    {{ $this->table }}
</x-filament-panels::page>
