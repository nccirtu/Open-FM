@props(['plugin', 'compact' => false])

@php
    $definition = \App\Support\Plugins\Plugins::definition($plugin);
    $canManage = \App\Support\Plugins\Plugins::canManage();
@endphp

<x-filament::empty-state
    :heading="__('plugins.required.heading', ['plugin' => $definition->name()])"
    :description="\App\Support\Plugins\Plugins::unavailableReason($plugin).' '.($canManage ? __('plugins.required.install_hint') : __('plugins.required.contact_admin'))"
    :icon="$definition->icon"
    icon-color="gray"
    :compact="$compact"
>
    @if ($canManage)
        <x-slot name="footer">
            <x-filament::button
                tag="a"
                :href="\App\Filament\Admin\Pages\ManagePlugins::getUrl(['plugin' => $plugin], panel: 'admin')"
                icon="heroicon-o-puzzle-piece"
            >
                {{ __('plugins.required.manage') }}
            </x-filament::button>
        </x-slot>
    @endif
</x-filament::empty-state>
