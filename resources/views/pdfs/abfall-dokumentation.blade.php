@php
    $format = fn (?float $value, int $decimals = 1): string => $value === null ? '—' : number_format($value, $decimals, ',', '.');
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ __('waste.documentation.pdf_title', ['year' => $doc['year']]) }}</title>
    <style>
        body { font-family: 'DejaVu Sans', 'Helvetica', sans-serif; font-size: 9px; color: #333; }
        @page { margin: 35px 30px; }
        .header { width: 100%; margin-bottom: 15px; border-bottom: 2px solid #005A9C; padding-bottom: 8px; }
        .header-title { font-size: 18px; font-weight: bold; color: #005A9C; }
        .header-sub { font-size: 10px; color: #666; }
        h2 { font-size: 12px; color: #005A9C; padding-left: 5px; border-left: 4px solid #005A9C; margin-top: 16px; margin-bottom: 6px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { border: 1px solid #ccc; padding: 4px 5px; text-align: left; vertical-align: top; }
        th { background-color: #f2f2f2; }
        td.num { text-align: right; }
        .ok { color: #15803d; font-weight: bold; }
        .open { color: #b91c1c; font-weight: bold; }
        .muted { color: #888; }
        .footer { margin-top: 20px; font-size: 8px; color: #999; }
    </style>
</head>
<body>

<div class="header">
    <span class="header-title">{{ __('waste.documentation.pdf_title', ['year' => $doc['year']]) }}</span><br>
    <span class="header-sub">
        {{ $doc['property']->property_number }} · {{ collect([$doc['property']->street, trim(($doc['property']->zip_code ?? '').' '.($doc['property']->city ?? ''))])->filter()->implode(', ') }}<br>
        {{ __('reports.generated_at') }} {{ now()->format('d.m.Y H:i') }}
    </span>
</div>

<h2>{{ __('waste.documentation.separation_rate') }}</h2>
<table>
    <tr>
        <th style="width: 40%">{{ __('waste.documentation.separation_rate') }}</th>
        <td>{{ $format($doc['separation']['rate']) }} % ({{ $doc['separation']['target'] !== null ? __('waste.documentation.target', ['value' => $format($doc['separation']['target'], 0)]) : __('waste.documentation.no_target') }})
            @if ($doc['separation']['met'] !== null)
                — <span class="{{ $doc['separation']['met'] ? 'ok' : 'open' }}">{{ $doc['separation']['met'] ? __('waste.documentation.met') : __('waste.documentation.not_met') }}</span>
            @endif
        </td>
    </tr>
    @if ($doc['separation']['due'])
        <tr>
            <th>{{ __('waste.documentation.quota_due_label') }}</th>
            <td>{{ $doc['separation']['due']->format('d.m.Y') }}</td>
        </tr>
    @endif
</table>

<h2>{{ __('waste.documentation.quantities') }}</h2>
@if ($doc['fractions']->isEmpty())
    <p class="muted">{{ __('waste.flow.empty') }}</p>
@else
    <table>
        <thead>
        <tr>
            <th>{{ __('waste.models.fraction.singular') }}</th>
            <th>{{ __('waste.fields.waste_code') }}</th>
            <th>{{ __('waste.models.fraction_group.singular') }}</th>
            <th class="num">{{ __('waste.fields.mass_t') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($doc['fractions'] as $row)
            <tr>
                <td>{{ $row['fraction']->name }}</td>
                <td>{{ $row['fraction']->code ?? '—' }}</td>
                <td>{{ $row['fraction']->group?->name ?? '—' }}</td>
                <td class="num">{{ $format($row['mass'] / 1000, 3) }}</td>
            </tr>
        @endforeach
        <tr>
            <th colspan="3">{{ __('waste.documentation.total_label') }}</th>
            <th class="num">{{ $format($doc['mass_t'], 3) }}</th>
        </tr>
        </tbody>
    </table>
@endif

<h2>{{ __('waste.documentation.checklist', ['year' => $doc['year']]) }}</h2>
<table>
    <thead>
    <tr>
        <th style="width: 28%">{{ __('waste.fields.evidence_type') }}</th>
        <th style="width: 14%">{{ __('waste.fields.status') }}</th>
        <th>{{ __('waste.fields.details') }}</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($doc['items'] as $item)
        <tr>
            <td>{{ $item['label'] }}</td>
            <td class="{{ $item['status'] === \App\Enums\WasteEvidenceStatus::Available ? 'ok' : ($item['status'] === \App\Enums\WasteEvidenceStatus::Open ? 'open' : 'muted') }}">{{ $item['status']->getLabel() }}</td>
            <td>
                {{ $item['hint'] }}
                @if (filled($item['basis']))<br><span class="muted">{{ __('waste.documentation.basis') }}: {{ $item['basis'] }}</span>@endif
                @if ($item['evidence'])
                    @if ($item['evidence']->valid_until)<br>{{ __('waste.fields.valid_until') }}: {{ $item['evidence']->valid_until->format('d.m.Y') }}@endif
                    @if (filled($item['evidence']->attachments))<br>{{ __('waste.fields.attachments') }}: {{ collect($item['evidence']->attachments)->map(fn ($file) => basename($file))->implode(', ') }}@endif
                    @if (filled($item['evidence']->note))<br>{{ $item['evidence']->note }}@endif
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="footer">{{ __('waste.documentation.pdf_footer') }}</div>

</body>
</html>
