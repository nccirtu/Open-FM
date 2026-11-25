<x-filament-panels::page>
    @php($bimModel = $this->getBimModel())

    @if (! \App\Support\Plugins\Plugins::active('bim-viewer'))
        <x-plugin-required plugin="bim-viewer" />
    @elseif ($bimModel === null)
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('property.bim.empty') }}
            </p>
        </x-filament::section>
    @else
        <x-filament::section>
            <x-slot name="heading">{{ $bimModel->name }}</x-slot>
            <x-slot name="description">{{ __('property.bim.uploaded_at') }} {{ $bimModel->created_at->format('d.m.Y H:i') }}</x-slot>

            @include('bim-viewer::viewer', [
                'fileUrl' => route('bim-models.file', $bimModel),
                'importUrl' => route('bim-models.import', $bimModel),
                'onSelect' => 'inspectElement',
                'focusGuid' => request('highlight'),
            ])
        </x-filament::section>
    @endif
</x-filament-panels::page>
