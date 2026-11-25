<x-filament-widgets::widget>
    <x-filament::section :heading="__('energy.goal.models').' '.$this->filters()->year()">
        <x-slot name="afterHeader">
            <x-filament::link :href="\App\Filament\Energy\Resources\EnergyGoalResource::getUrl('index')" size="sm">
                {{ __('energy.actions.all_goals') }}
            </x-filament::link>
        </x-slot>

        @php($goals = $this->getGoals())

        @if ($goals->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('energy.goal.empty') }}</p>
        @else
            <div class="flex flex-col gap-4">
                @foreach ($goals as $row)
                    <div wire:key="goal-{{ $row['goal']->getKey() }}" class="flex flex-col gap-1">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-gray-950 dark:text-white">{{ $row['goal']->name }}</p>
                                <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ \App\Filament\Energy\Resources\EnergyGoalResource::scopeLabel($row['goal']) }}</p>
                            </div>
                            <x-filament::badge :color="\App\Filament\Energy\Resources\EnergyGoalResource::statusColor($row['result']['status'])" size="sm">
                                {{ __('energy.goal_status.'.$row['result']['status']) }}
                            </x-filament::badge>
                        </div>
                        @include('filament.energy.goal-bar', ['result' => $row['result']])
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
