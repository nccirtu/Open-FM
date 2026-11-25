<div class="divide-y divide-gray-200 dark:divide-white/10">
    @foreach ($pickups as $pickup)
        <div class="flex items-start justify-between gap-3 py-2.5">
            <div class="min-w-0">
                <div class="font-medium text-gray-950 dark:text-white">{{ $pickup->container?->label() }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ collect([$pickup->property?->property_number, $pickup->container?->structureNode?->fullPath()])->filter()->implode(' › ') }}
                </div>
                @if ($pickup->collection)
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        {{ number_format((float) $pickup->collection->mass_kg, 1, ',', '.') }} kg · {{ $pickup->collection->mass_source?->getLabel() }}
                    </div>
                @endif
                @if ($pickup->missed_reason)
                    <div class="text-sm text-danger-600 dark:text-danger-400">{{ $pickup->missed_reason->getLabel() }}</div>
                @endif
            </div>
            <x-filament::badge :color="$pickup->status->getColor()" :icon="$pickup->status->getIcon()" class="shrink-0">
                {{ $pickup->statusLabel() }}
            </x-filament::badge>
        </div>
    @endforeach
</div>
