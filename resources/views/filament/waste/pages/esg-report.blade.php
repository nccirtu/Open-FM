@php
    $report = $this->getReport();
    $t = fn (?float $value): string => $value === null ? '—' : number_format($value, 2, ',', '.');
    $pct = fn (?float $value): string => $value === null ? '—' : number_format($value, 1, ',', '.').' %';
    $esrsRows = [
        ['label' => __('waste.esg.diverted'), 'values' => $report['diverted'], 'strong' => true],
        ['label' => \App\Enums\WasteEsrsCategory::Reuse->getLabel(), 'values' => $report['esrs']['reuse']],
        ['label' => \App\Enums\WasteEsrsCategory::Recycling->getLabel(), 'values' => $report['esrs']['recycling']],
        ['label' => \App\Enums\WasteEsrsCategory::OtherRecovery->getLabel(), 'values' => $report['esrs']['other_recovery']],
        ['label' => __('waste.esg.disposal'), 'values' => $report['disposal'], 'strong' => true],
        ['label' => \App\Enums\WasteEsrsCategory::Incineration->getLabel(), 'values' => $report['esrs']['incineration']],
        ['label' => \App\Enums\WasteEsrsCategory::Landfill->getLabel(), 'values' => $report['esrs']['landfill']],
        ['label' => \App\Enums\WasteEsrsCategory::OtherDisposal->getLabel(), 'values' => $report['esrs']['other_disposal']],
        ['label' => __('waste.esg.unknown'), 'values' => $report['esrs']['unknown'], 'strong' => true],
        ['label' => __('waste.esg.total_generated'), 'values' => ['hazardous' => $report['mass']['hazardous'], 'non_hazardous' => $report['mass']['non_hazardous'], 'total' => $report['mass']['total']], 'strong' => true],
    ];
@endphp

<x-filament-panels::page>
    {{ $this->filtersForm }}

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => __('waste.esg.total_generated'), 'value' => $t($report['mass']['total']).' t'],
            ['label' => __('waste.esg.diversion_rate'), 'value' => $pct($report['diversion_rate'])],
            ['label' => __('waste.esg.coverage_properties'), 'value' => $pct($report['coverage']['share']), 'hint' => __('waste.esg.coverage_count', ['count' => $report['coverage']['properties'], 'total' => $report['coverage']['properties_total']])],
            ['label' => __('waste.stats.co2e'), 'value' => $report['co2e']['coverage'] !== null ? $t($report['co2e']['kg'] / 1000).' t' : '—', 'hint' => $report['co2e']['coverage'] !== null ? __('waste.stats.co2e_coverage', ['coverage' => number_format($report['co2e']['coverage'], 0, ',', '.')]) : __('waste.stats.co2e_help')],
        ] as $card)
            <x-filament::section compact>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $card['label'] }}</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-gray-950 dark:text-white">{{ $card['value'] }}</p>
                @if (filled($card['hint'] ?? null))
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $card['hint'] }}</p>
                @endif
            </x-filament::section>
        @endforeach
    </div>

    <x-filament::section heading="ESRS E5-5" :description="__('waste.esg.esrs_help')">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-left text-gray-500 dark:border-white/10 dark:text-gray-400">
                        <th class="py-2 pe-4 font-medium">{{ __('waste.esg.metric') }}</th>
                        <th class="py-2 pe-4 text-end font-medium">{{ __('waste.esg.hazardous') }}</th>
                        <th class="py-2 pe-4 text-end font-medium">{{ __('waste.esg.non_hazardous') }}</th>
                        <th class="py-2 text-end font-medium">{{ __('waste.esg.total') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @foreach ($esrsRows as $row)
                        <tr @class(['font-semibold text-gray-950 dark:text-white' => $row['strong'] ?? false, 'text-gray-700 dark:text-gray-300' => ! ($row['strong'] ?? false)])>
                            <td @class(['py-2 pe-4', 'ps-4' => ! ($row['strong'] ?? false)])>{{ $row['label'] }}</td>
                            <td class="py-2 pe-4 text-end tabular-nums">{{ $t($row['values']['hazardous']) }} t</td>
                            <td class="py-2 pe-4 text-end tabular-nums">{{ $t($row['values']['non_hazardous']) }} t</td>
                            <td class="py-2 text-end tabular-nums">{{ $t($row['values']['total']) }} t</td>
                        </tr>
                    @endforeach
                    @if ($report['construction_t'] > 0 && $report['kind'] === \App\Support\WasteFilters::KIND_ALL)
                        <tr class="text-gray-700 dark:text-gray-300">
                            <td class="py-2 pe-4 ps-4">{{ __('waste.esg.construction') }}</td>
                            <td class="py-2 pe-4"></td>
                            <td class="py-2 pe-4"></td>
                            <td class="py-2 text-end tabular-nums">{{ $t($report['construction_t']) }} t</td>
                        </tr>
                    @endif
                    <tr class="text-gray-700 dark:text-gray-300">
                        <td class="py-2 pe-4">{{ __('waste.esg.non_recycled') }}</td>
                        <td class="py-2 pe-4"></td>
                        <td class="py-2 pe-4 text-end tabular-nums">{{ $pct($report['non_recycled']['share']) }}</td>
                        <td class="py-2 text-end tabular-nums">{{ $t($report['non_recycled']['total']) }} t</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </x-filament::section>

    <div class="grid gap-6 lg:grid-cols-3">
        <x-filament::section heading="VSME B7" :description="__('waste.esg.vsme_help')">
            <dl class="grid grid-cols-[1fr_auto] gap-x-4 gap-y-2 text-sm">
                <dt class="text-gray-500 dark:text-gray-400">{{ __('waste.esg.total_generated') }}</dt>
                <dd class="text-end tabular-nums text-gray-950 dark:text-white">{{ $t($report['mass']['total']) }} t</dd>
                <dt class="text-gray-500 dark:text-gray-400">{{ __('waste.esg.hazardous') }}</dt>
                <dd class="text-end tabular-nums text-gray-950 dark:text-white">{{ $t($report['mass']['hazardous']) }} t</dd>
                <dt class="text-gray-500 dark:text-gray-400">{{ __('waste.esg.recycled_or_reused') }}</dt>
                <dd class="text-end tabular-nums text-gray-950 dark:text-white">{{ $t($report['recycled_or_reused']) }} t</dd>
            </dl>
        </x-filament::section>

        <x-filament::section heading="GRESB" :description="__('waste.esg.gresb_help')">
            <dl class="grid grid-cols-[1fr_auto] gap-x-4 gap-y-2 text-sm">
                <dt class="text-gray-500 dark:text-gray-400">{{ __('waste.esg.coverage_properties') }}</dt>
                <dd class="text-end tabular-nums text-gray-950 dark:text-white">{{ $pct($report['coverage']['share']) }}</dd>
                <dt class="text-gray-500 dark:text-gray-400">{{ __('waste.esg.coverage_area') }}</dt>
                <dd class="text-end tabular-nums text-gray-950 dark:text-white">{{ $pct($report['coverage']['area_share']) }}</dd>
                <dt class="text-gray-500 dark:text-gray-400">{{ __('waste.esg.diverted') }}</dt>
                <dd class="text-end tabular-nums text-gray-950 dark:text-white">{{ $t($report['diverted']['total']) }} t</dd>
            </dl>
        </x-filament::section>

        <x-filament::section heading="LEED O+M" :description="__('waste.esg.leed_help')">
            <dl class="grid grid-cols-[1fr_auto] gap-x-4 gap-y-2 text-sm">
                <dt class="text-gray-500 dark:text-gray-400">{{ __('waste.esg.diversion_rate') }}</dt>
                <dd class="text-end tabular-nums text-gray-950 dark:text-white">{{ $pct($report['diversion_rate']) }}</dd>
                <dt class="text-gray-500 dark:text-gray-400">{{ __('waste.esg.recycled_or_reused') }}</dt>
                <dd class="text-end tabular-nums text-gray-950 dark:text-white">{{ $t($report['recycled_or_reused']) }} t</dd>
            </dl>
        </x-filament::section>
    </div>
</x-filament-panels::page>
