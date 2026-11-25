@php
    $doc = $this->getDocumentation();
    $format = fn (?float $value, int $decimals = 1): string => $value === null ? '—' : number_format($value, $decimals, ',', '.');
@endphp

<x-filament-panels::page>
    {{ $this->filtersForm }}

    @if ($doc === null)
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('waste.documentation.no_property') }}</p>
        </x-filament::section>
    @else
        <div class="grid gap-6 lg:grid-cols-3">
            <x-filament::section :heading="__('waste.documentation.separation_rate')" :description="__('waste.documentation.separation_rate_help')">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <p class="text-3xl font-semibold tabular-nums text-gray-950 dark:text-white">{{ $format($doc['separation']['rate']) }} %</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $doc['separation']['target'] !== null ? __('waste.documentation.target', ['value' => $format($doc['separation']['target'], 0)]) : __('waste.documentation.no_target') }}</p>
                    </div>
                    @if ($doc['separation']['met'] !== null)
                        <x-filament::badge :color="$doc['separation']['met'] ? 'success' : 'danger'" :icon="$doc['separation']['met'] ? 'heroicon-m-check' : 'heroicon-m-x-mark'">
                            {{ $doc['separation']['met'] ? __('waste.documentation.met') : __('waste.documentation.not_met') }}
                        </x-filament::badge>
                    @endif
                </div>
                @if ($doc['separation']['due'])
                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">{{ __('waste.documentation.quota_due', ['date' => $doc['separation']['due']->format('d.m.Y')]) }}</p>
                @endif
            </x-filament::section>

            <x-filament::section :heading="__('waste.documentation.quantities')" class="lg:col-span-2">
                @if ($doc['fractions']->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('waste.flow.empty') }}</p>
                @else
                    <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm">
                        @foreach ($doc['fractions'] as $row)
                            <span class="inline-flex items-center gap-2">
                                <span class="size-2.5 rounded-sm" style="background-color: {{ $row['fraction']->displayColor() }}"></span>
                                <span class="text-gray-700 dark:text-gray-200">{{ $row['fraction']->name }}</span>
                                <span class="tabular-nums text-gray-500 dark:text-gray-400">{{ $format($row['mass'] / 1000, 2) }} t</span>
                                @if ($row['fraction']->group && ! $row['fraction']->group->counts_as_separated)
                                    <x-filament::badge color="warning" size="sm">{{ __('waste.documentation.mixed') }}</x-filament::badge>
                                @endif
                            </span>
                        @endforeach
                    </div>
                    <p class="mt-3 text-sm font-medium text-gray-950 dark:text-white">{{ __('waste.documentation.total', ['mass' => $format($doc['mass_t'], 2)]) }}</p>
                @endif
            </x-filament::section>
        </div>

        <x-filament::section :heading="__('waste.documentation.checklist', ['year' => $doc['year']])">
            <x-slot name="afterHeader">
                <x-filament::badge :color="$doc['complete'] ? 'success' : 'warning'">
                    {{ $doc['complete'] ? __('waste.documentation.complete') : __('waste.documentation.incomplete') }}
                </x-filament::badge>
            </x-slot>

            <div class="divide-y divide-gray-200 dark:divide-white/10">
                @foreach ($doc['items'] as $item)
                    <div class="flex items-start gap-3 py-3" wire:key="evidence-{{ $item['type'] }}">
                        <x-filament::icon :icon="$item['status']->getIcon()" @class([
                            'mt-0.5 size-5 shrink-0',
                            'text-success-600 dark:text-success-400' => $item['status'] === \App\Enums\WasteEvidenceStatus::Available,
                            'text-danger-600 dark:text-danger-400' => $item['status'] === \App\Enums\WasteEvidenceStatus::Open,
                            'text-gray-400' => $item['status'] === \App\Enums\WasteEvidenceStatus::NotRequired,
                        ]) />
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-gray-950 dark:text-white">{{ $item['label'] }}</p>
                            @if (filled($item['hint']))
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $item['hint'] }}</p>
                            @endif
                            @if (filled($item['basis']))
                                <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">
                                    {{ __('waste.documentation.basis') }}: {{ \Illuminate\Support\Str::limit($item['basis'], 220) }}
                                    @if (filled($item['url']))
                                        · <a href="{{ $item['url'] }}" target="_blank" rel="noopener" class="text-primary-600 hover:underline dark:text-primary-400">{{ __('waste.settings.source') }}</a>
                                    @endif
                                </p>
                            @endif
                            @if ($item['evidence'])
                                <p class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                                    @if ($item['evidence']->valid_until)
                                        <span>{{ __('waste.fields.valid_until') }}: {{ $item['evidence']->valid_until->format('d.m.Y') }}</span>
                                    @endif
                                    @foreach ($item['evidence']->attachments ?? [] as $file)
                                        <a href="{{ \App\Support\Uploads::url($file) }}" target="_blank" class="text-primary-600 hover:underline dark:text-primary-400">{{ basename($file) }}</a>
                                    @endforeach
                                    @if (filled($item['evidence']->note))
                                        <span>{{ $item['evidence']->note }}</span>
                                    @endif
                                </p>
                            @endif
                        </div>
                        <x-filament::badge :color="$item['status']->getColor()" class="shrink-0">{{ $item['status']->getLabel() }}</x-filament::badge>
                        {{ ($this->editEvidenceAction)(['type' => $item['type']]) }}
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endif

</x-filament-panels::page>
