
<div class="flex flex-col gap-3">
    @unless ($country)
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('waste.settings.no_country') }}</p>
    @endunless

    <div class="divide-y divide-gray-200 dark:divide-white/10">
        @foreach ($rows as $row)
            <div class="grid gap-1 py-3 sm:grid-cols-[minmax(0,14rem)_minmax(0,10rem)_minmax(0,1fr)] sm:gap-4" wire:key="rule-{{ $row['key'] }}">
                <p class="text-sm font-medium text-gray-950 dark:text-white">{{ $row['label'] }}</p>
                <p @class([
                    'text-sm tabular-nums',
                    'font-semibold text-gray-950 dark:text-white' => $row['applies'],
                    'text-gray-500 dark:text-gray-400' => ! $row['applies'],
                ])>
                    {{ $row['value'] }}
                    @if ($row['default'])
                        <span class="text-xs font-normal text-gray-500 dark:text-gray-400">({{ __('waste.settings.default') }})</span>
                    @endif
                </p>
                <div class="min-w-0 text-sm text-gray-600 dark:text-gray-300">
                    @if (filled($row['basis']))
                        <p class="break-words">{{ $row['basis'] }}</p>
                    @endif
                    @if (filled($row['url']))
                        <a href="{{ $row['url'] }}" target="_blank" rel="noopener" class="text-xs text-primary-600 hover:underline dark:text-primary-400">{{ __('waste.settings.source') }}</a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
