@php
    $target = (float) ($result['target'] ?? 0);
    $range = $target > 0 ? $target * 1.2 : 1;
    $pct = fn (float $value): float => round(min(100, max(0, $value / $range * 100)), 1);
    $color = match ($result['status']) {
        'good' => 'var(--success-500)',
        'warn' => 'var(--warning-500)',
        'crit' => 'var(--danger-500)',
        default => 'var(--gray-400)',
    };
    $format = fn (float $value): string => number_format($value, $value < 100 ? 1 : 0, ',', '.');
@endphp
<div style="display: grid; gap: 4px; min-width: 180px; padding-block: 4px;">
    <div style="position: relative; height: 10px; border-radius: 0 4px 4px 0;" class="bg-gray-200 dark:bg-gray-700">
        @if ($target > 0)
            @if ($result['forecast'] !== null)
                <div style="position: absolute; inset-block: 0; left: 0; width: {{ $pct((float) $result['forecast']) }}%; background: {{ $color }}; opacity: .35; border-radius: 0 4px 4px 0;"></div>
            @endif
            <div style="position: absolute; inset-block: 0; left: 0; width: {{ $pct((float) ($result['actual'] ?? 0)) }}%; background: {{ $color }}; border-radius: 0 4px 4px 0;"></div>
            <div style="position: absolute; top: -3px; bottom: -3px; left: calc({{ $pct($target) }}% - 1px); width: 2px;" class="bg-gray-950 dark:bg-white"></div>
        @endif
    </div>
    <div style="display: flex; flex-wrap: wrap; gap: 2px 10px; font-size: 0.75rem; font-variant-numeric: tabular-nums;" class="text-gray-500 dark:text-gray-400">
        <span>{{ __('waste.goal.actual') }} {{ $result['actual'] !== null ? $format((float) $result['actual']).' '.$result['unit'] : '—' }}</span>
        @if ($result['forecast'] !== null)
            <span>{{ __('waste.goal.forecast') }} {{ $format((float) $result['forecast']) }}</span>
        @endif
        @if ($target > 0)
            <span>{{ __('waste.goal.target') }} {{ $format($target) }} {{ $result['unit'] }}</span>
        @endif
    </div>
</div>
