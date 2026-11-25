<x-filament::dropdown placement="bottom-end" teleport>
    <x-slot name="trigger">
        <x-filament::icon-button
            icon="heroicon-o-language"
            :label="__('app.language.choose')"
            color="gray"
            size="lg"
        />
    </x-slot>

    <x-filament::dropdown.list>
        @foreach (\App\Models\Organization::LOCALES as $locale => $label)
            <x-filament::dropdown.list.item
                tag="a"
                :href="route('locale.switch', ['locale' => $locale])"
                :icon="app()->getLocale() === $locale ? 'heroicon-m-check' : null"
            >
                {{ $label }}
            </x-filament::dropdown.list.item>
        @endforeach
    </x-filament::dropdown.list>
</x-filament::dropdown>
