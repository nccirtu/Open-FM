<x-filament-widgets::widget>
    <x-filament::section :heading="__('waste.flow.title')" :description="$this->filters()->label()">
        @php
            $layout = $this->getLayout();
            $colors = collect($layout['nodes'])->mapWithKeys(fn (array $node): array => [$node['id'] => $node['color'] ?? '#94a3b8']);
        @endphp

        @if ($layout['nodes'] === [])
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('waste.flow.empty') }}</p>
        @else
            <div class="overflow-x-auto">
                <svg viewBox="0 0 {{ $layout['width'] }} {{ $layout['height'] }}" role="img" aria-label="{{ __('waste.flow.title') }}" style="width: 100%; min-width: 480px; height: auto;" class="text-gray-950 dark:text-white">
                    @foreach ($layout['links'] as $link)
                        <path d="{{ $link['path'] }}" fill="{{ $colors[$link['source']] ?? '#94a3b8' }}" fill-opacity="0.35" class="transition hover:[fill-opacity:0.6]">
                            <title>{{ $link['label'] }}: {{ number_format($link['value'], 2, ',', '.') }} t</title>
                        </path>
                    @endforeach
                    @foreach ($layout['nodes'] as $node)
                        <rect x="{{ $node['x'] }}" y="{{ $node['y'] }}" width="{{ $layout['nodeWidth'] ?? 14 }}" height="{{ max(2, $node['h']) }}" rx="2" fill="{{ $node['color'] ?? '#64748b' }}">
                            <title>{{ $node['label'] }}: {{ number_format($node['value'], 2, ',', '.') }} t</title>
                        </rect>
                        <text x="{{ $node['x'] + ($layout['nodeWidth'] ?? 14) + 6 }}" y="{{ $node['y'] + $node['h'] / 2 + 4 }}" font-size="12" fill="currentColor" stroke-width="3" paint-order="stroke" stroke-linejoin="round" class="stroke-white dark:stroke-gray-900">
                            {{ $node['label'] }}
                            <tspan fill-opacity="0.6">{{ number_format($node['value'], 1, ',', '.') }} t</tspan>
                        </text>
                    @endforeach
                </svg>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
