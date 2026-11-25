@if (! \App\Support\Plugins\Plugins::active('bim-viewer'))
    <x-plugin-required plugin="bim-viewer" compact />
@elseif ($bimModel === null)
    <p class="text-sm text-gray-500 dark:text-gray-400">
        {{ __('property.bim.empty') }}
    </p>
@else
    @include('bim-viewer::viewer', [
        'fileUrl' => route('bim-models.file', $bimModel),
        'focusGuid' => $guid,
        'height' => '70vh',
    ])
@endif
