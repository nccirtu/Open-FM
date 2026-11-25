<x-filament-panels::page>
    {{ $this->filtersForm }}

    @if ($this->getYearlySaving() > 0)
        <x-filament::section compact>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('waste.rightsizing.total_saving') }}</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums text-gray-950 dark:text-white">{{ number_format($this->getYearlySaving(), 0, ',', '.') }} €</p>
        </x-filament::section>
    @endif

    {{ $this->table }}
</x-filament-panels::page>
