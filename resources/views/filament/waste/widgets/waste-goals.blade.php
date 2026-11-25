<x-filament-widgets::widget>
    <x-filament::section :heading="__('waste.models.goal.plural').' '.$this->filters()->year()">
        <x-slot name="afterHeader">
            <x-filament::link :href="\App\Filament\Waste\Resources\WasteGoals\WasteGoalResource::getUrl('index')" size="sm">
                {{ __('waste.actions.all_goals') }}
            </x-filament::link>
        </x-slot>

        @php($goals = $this->getGoals())

        @if ($goals->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('waste.goal.empty') }}</p>
        @else
            <div class="flex flex-col gap-4">
                @foreach ($goals as $row)
                    <div wire:key="goal-{{ $row['goal']->getKey() }}" class="flex flex-col gap-1">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-gray-950 dark:text-white">{{ $row['goal']->name }}</p>
                                <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ \App\Filament\Waste\Resources\WasteGoals\WasteGoalResource::scopeLabel($row['goal']) }} · {{ \App\Filament\Waste\Resources\WasteGoals\WasteGoalResource::targetText($row['goal']) }}</p>
                            </div>
                            <x-filament::badge :color="\App\Filament\Waste\Resources\WasteGoals\WasteGoalResource::statusColor($row['result']['status'])" size="sm">
                                {{ __('waste.goal_status.'.$row['result']['status']) }}
                            </x-filament::badge>
                        </div>
                        @include('filament.waste.goal-bar', ['result' => $row['result']])
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
