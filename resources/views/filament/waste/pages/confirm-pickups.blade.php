@php
    $pickups = $this->getPickups();
    $scanned = $this->getScannedContainer();
@endphp

<x-filament-panels::page>
    {{ $this->filtersForm }}

    @if ($scanned)
        <x-filament::section :heading="$scanned->label()" :description="collect([$scanned->property?->property_number, $scanned->structureNode?->fullPath()])->filter()->implode(' › ')">
            <div class="flex flex-wrap gap-2">
                {{ $this->requestPickupAction }}
            </div>
        </x-filament::section>
    @endif

    <x-filament::section
        :heading="__('waste.pages.open_pickups')"
        :description="__('waste.help.open_pickups')"
    >
        <div class="divide-y divide-gray-200 dark:divide-white/10">
            @forelse ($pickups as $pickup)
                @php($key = $pickup->getKey())

                <div class="flex flex-col gap-3 py-3 sm:flex-row sm:items-center" wire:key="pickup-{{ $key }}">
                    <label class="flex min-w-0 flex-1 cursor-pointer items-start gap-3">
                        <x-filament::input.checkbox wire:model.live="selected" value="{{ $key }}" class="mt-1" />

                        <span class="mt-1.5 size-3 shrink-0 rounded-sm" style="background-color: {{ $pickup->container?->fraction?->displayColor() ?? '#94a3b8' }}"></span>

                        <span class="min-w-0">
                            <span class="block font-medium text-gray-950 dark:text-white">{{ $pickup->container?->label() }}</span>
                            <span class="block text-sm text-gray-500 dark:text-gray-400">
                                {{ collect([$pickup->property?->property_number, $pickup->container?->structureNode?->fullPath()])->filter()->implode(' › ') }}
                                · {{ $pickup->due_on?->format('d.m.Y') }}
                            </span>
                        </span>

                        @if ($pickup->isCompletedByPlan())
                            <x-filament::badge color="gray" icon="heroicon-m-check" class="shrink-0">
                                {{ __('waste.pickup_status.by_plan') }}
                            </x-filament::badge>
                        @endif
                    </label>

                    <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                        <div class="inline-flex overflow-hidden rounded-lg ring-1 ring-gray-950/10 dark:ring-white/20" role="group" aria-label="{{ __('waste.fields.fill_level') }}">
                            @foreach (\App\Filament\Waste\Pages\ConfirmPickupsPage::FILL_LEVELS as $level)
                                <button
                                    type="button"
                                    wire:click="setFill(@js($key), {{ $level }})"
                                    @class([
                                        'px-2.5 py-1.5 text-sm tabular-nums transition',
                                        'bg-gray-950 font-semibold text-white dark:bg-white dark:text-gray-950' => ($this->fill[$key] ?? null) === $level,
                                        'text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-white/5' => ($this->fill[$key] ?? null) !== $level,
                                    ])
                                >
                                    {{ $level }} %
                                </button>
                            @endforeach
                        </div>

                        {{ ($this->confirmPickupAction)(['pickup' => $key]) }}
                        {{ ($this->missedAction)(['pickup' => $key]) }}
                    </div>
                </div>
            @empty
                <p class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('waste.pages.no_open_pickups') }}</p>
            @endforelse
        </div>

        @if ($pickups->isNotEmpty())
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('waste.help.fill_level_default') }}</p>
                {{ $this->confirmSelectedAction }}
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
