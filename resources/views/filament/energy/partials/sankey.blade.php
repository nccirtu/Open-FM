<div class="overflow-x-auto">
    <svg viewBox="0 0 {{ $layout['width'] }} {{ $layout['height'] }}" role="img" aria-label="{{ __('energy.flow.title') }}" style="width: 100%; min-width: 640px; height: auto;" class="text-gray-950 dark:text-white">
        @foreach ($layout['links'] as $link)
            <path d="{{ $link['path'] }}" fill="{{ $link['kind'] === 'meter' ? $layout['color'] : '#94a3b8' }}" fill-opacity="0.25" class="transition hover:[fill-opacity:0.5]">
                <title>{{ $link['label'] }}: {{ number_format($link['value'], 0, ',', '.') }} {{ $layout['unit'] }}</title>
            </path>
        @endforeach
        @foreach ($layout['nodes'] as $node)
            @php($url = $this->nodeUrl($node))
            <a @if ($url) href="{{ $url }}" class="cursor-pointer hover:opacity-80" @endif>
                <rect x="{{ $node['x'] }}" y="{{ $node['y'] }}" width="{{ $layout['nodeWidth'] }}" height="{{ max(2, $node['h']) }}" rx="2"
                      fill="{{ match ($node['kind']) { 'meter' => $layout['color'], 'rest' => '#cbd5e1', default => '#64748b' } }}">
                    <title>{{ $node['label'] }}: {{ number_format($node['value'], 0, ',', '.') }} {{ $layout['unit'] }}</title>
                </rect>
                <text x="{{ $node['x'] + $layout['nodeWidth'] + 6 }}" y="{{ $node['y'] + $node['h'] / 2 + 4 }}" font-size="12" fill="currentColor" stroke-width="3" paint-order="stroke" stroke-linejoin="round" class="stroke-white dark:stroke-gray-900">
                    {{ $node['label'] }}
                    <tspan fill-opacity="0.6">{{ number_format($node['value'], 0, ',', '.') }} {{ $layout['unit'] }}</tspan>
                </text>
            </a>
        @endforeach
    </svg>
</div>
