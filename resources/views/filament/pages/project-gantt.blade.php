<x-filament-panels::page>
    @if (\App\Support\Plugins\Plugins::active('gantt-chart'))
        @livewire(\App\Filament\Integrations\GanttChart\ProjectGanttWidget::class, ['project' => $this->record], key('project-gantt-'.$this->record->getKey()))
    @else
        <x-plugin-required plugin="gantt-chart" />
    @endif
</x-filament-panels::page>
