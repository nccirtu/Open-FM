@php
    $rows = $this->getRows();
    $counts = $this->getRegisterCounts();
    $format = fn (float $value): string => number_format($value, 2, ',', '.');
@endphp

<x-filament-widgets::widget>
    <x-filament::section :heading="__('waste.widgets.hazardous', ['year' => $this->filters()->year()])">
        <x-slot name="afterHeader">
            <x-filament::link :href="$this->getRegisterUrl()" size="sm">
                {{ __('waste.pages.register') }}
            </x-filament::link>
        </x-slot>

        <div class="mb-4 flex flex-wrap gap-2">
            <x-filament::badge :color="$counts['overdue'] > 0 ? 'danger' : 'gray'" icon="heroicon-m-clock">
                {{ trans_choice('waste.widgets.register_overdue', $counts['overdue'], ['count' => $counts['overdue']]) }}
            </x-filament::badge>
            <x-filament::badge :color="$counts['open'] > 0 ? 'warning' : 'gray'" icon="heroicon-m-pencil-square">
                {{ trans_choice('waste.widgets.register_open', $counts['open'], ['count' => $counts['open']]) }}
            </x-filament::badge>
        </div>

        @if ($rows->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('waste.widgets.hazardous_empty') }}</p>
        @else
            <div class="flex flex-col gap-3">
                @foreach ($rows as $row)
                    @php($over = $row['share'] !== null && $row['share'] >= 1)
                    <div wire:key="hazardous-{{ $row['property']->getKey() }}" class="flex flex-col gap-1">
                        <div class="flex items-baseline justify-between gap-2 text-sm">
                            <span class="truncate font-medium text-gray-950 dark:text-white">{{ $row['property']->property_number }}</span>
                            <span class="shrink-0 tabular-nums text-gray-500 dark:text-gray-400">{{ $format($row['mass_t']) }}{{ $row['threshold_t'] !== null ? ' / '.$format($row['threshold_t']) : '' }} t</span>
                        </div>
                        @if ($row['share'] !== null)
                            <div class="h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                <div class="h-full rounded-full" style="width: {{ min(100, round($row['share'] * 100)) }}%; background: {{ $over ? 'var(--danger-500)' : ($row['share'] >= 0.8 ? 'var(--warning-500)' : 'var(--success-500)') }}"></div>
                            </div>
                        @endif
                        @if ($over)
                            <p class="text-xs text-danger-600 dark:text-danger-400">{{ __('waste.widgets.hazardous_over') }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
