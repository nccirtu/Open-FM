<x-filament-widgets::widget>
    <x-filament::section :heading="__('energy.flow.title')" :description="__('energy.flow.description').' '.$this->filters()->label()">
        <x-slot name="afterHeader">
            <div class="flex flex-wrap items-center gap-2">
                @unless ($this->hasFixedProperty())
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="propertyId" :aria-label="__('property.model')">
                            @foreach ($this->getPropertyOptions() as $id => $label)
                                <option value="{{ $id }}">{{ $label }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                @endunless
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="medium" :aria-label="__('energy.fields.medium')">
                        @foreach ($this->getMediumOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
        </x-slot>

        @php($layout = $this->getLayout())

        @if ($layout['nodes'] === [])
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('energy.flow.empty') }}</p>
        @else
            @include('filament.energy.partials.sankey', ['layout' => $layout])
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
