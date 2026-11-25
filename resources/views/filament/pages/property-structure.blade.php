<x-filament-panels::page>
    @if (\App\Support\Plugins\Plugins::active('structure-tree'))
        @livewire(\App\Filament\Integrations\StructureTree\StructureTreeWidget::class, ['property' => $this->record], key('structure-tree-'.$this->record->getKey()))
    @else
        <x-plugin-required plugin="structure-tree" />
    @endif
</x-filament-panels::page>
