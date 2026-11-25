<x-filament-widgets::widget>
    <x-filament::section :heading="__('energy.consumers.title')" :description="__('energy.consumers.description')">
        <x-slot name="afterHeader">
            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="year" :aria-label="__('energy.fields.year')">
                    @foreach ($this->getYearOptions() as $year)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </x-slot>

        @if (! $this->hasConsumers())
            <div class="flex items-start gap-3 text-sm text-gray-600 dark:text-gray-300">
                <x-filament::icon icon="heroicon-o-information-circle" class="size-5 shrink-0 text-gray-400" />
                <p>{{ __('energy.consumers.empty') }}</p>
            </div>
        @else
            @php($data = $this->getData())

            @if ($data['consumers'] === [])
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('energy.consumers.no_consumption') }}</p>
            @else
                <div class="grid gap-6 lg:grid-cols-5">
                    <div class="lg:col-span-3">
                        @include('filament.energy.partials.sankey', ['layout' => $data['layout']])
                    </div>

                    <div class="lg:col-span-2">
                        <h3 class="mb-3 text-sm font-medium text-gray-950 dark:text-white">{{ __('energy.consumers.list') }}</h3>
                        <ul class="flex flex-col gap-3">
                            @foreach ($data['consumers'] as $consumer)
                                @php($url = $this->nodeUrl($consumer))
                                <li wire:key="consumer-{{ $consumer['id'] }}" class="flex flex-col gap-1">
                                    <div class="flex items-baseline justify-between gap-3 text-sm">
                                        @if ($url)
                                            <a href="{{ $url }}" class="truncate font-medium text-gray-950 hover:underline dark:text-white">{{ $consumer['label'] }}</a>
                                        @else
                                            <span class="truncate text-gray-500 dark:text-gray-400">{{ $consumer['label'] }}</span>
                                        @endif
                                        <span class="shrink-0 tabular-nums text-gray-600 dark:text-gray-300">
                                            {{ number_format($consumer['value'], 0, ',', '.') }} {{ $data['unit'] }}
                                            <span class="text-gray-400">· {{ number_format($consumer['percent'], 0, ',', '.') }} %</span>
                                        </span>
                                    </div>
                                    <div class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                                        <div class="h-full rounded-full" style="width: {{ max(1, min(100, $consumer['percent'])) }}%; background-color: {{ match ($consumer['kind']) { 'meter' => $data['color'], 'rest' => '#cbd5e1', default => '#64748b' } }};"></div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                        <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">{{ __('energy.consumers.hint', ['unassigned' => __('energy.flow.unassigned')]) }}</p>
                    </div>
                </div>
            @endif
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
