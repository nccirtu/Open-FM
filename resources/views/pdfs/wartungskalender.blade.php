<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ __('reports.calendar.title', ['year' => $year]) }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 9px; color: #333; }
        @page { margin: 35px 25px; }
        .header { width: 100%; margin-bottom: 15px; border-bottom: 2px solid #005A9C; padding-bottom: 8px; }
        .header-title { font-size: 20px; font-weight: bold; color: #005A9C; }
        .header-sub { font-size: 10px; color: #666; }
        h2 { font-size: 12px; color: #005A9C; padding-left: 5px; border-left: 4px solid #005A9C; margin-top: 16px; margin-bottom: 6px; }
        table.matrix { width: 100%; border-collapse: collapse; margin-bottom: 8px; page-break-inside: avoid; }
        table.matrix th, table.matrix td { border: 1px solid #ccc; padding: 3px 4px; text-align: center; }
        table.matrix th { background-color: #f2f2f2; }
        table.matrix td.name, table.matrix th.name { text-align: left; width: 26%; }
        table.matrix td.due { background-color: #dbeafe; font-weight: bold; color: #1d4ed8; }
        .empty-hint { color: #888; font-style: italic; }
        .footer { margin-top: 20px; font-size: 8px; color: #999; }
    </style>
</head>
<body>

<div class="header">
    <span class="header-title">{{ __('reports.calendar.title', ['year' => $year]) }}</span><br>
    <span class="header-sub">{{ __('reports.generated_at') }} {{ now()->format('d.m.Y H:i') }}</span>
</div>

@if($byProperty->isEmpty())
    <p class="empty-hint">{{ __('reports.calendar.empty') }}</p>
@endif

@foreach($byProperty as $propertyLabel => $rows)
    <h2>{{ $propertyLabel }}</h2>
    <table class="matrix">
        <thead>
        <tr>
            <th class="name">{{ __('maintenance.plans.model') }} / {{ __('maintenance.fields.asset') }}</th>
            @foreach(range(1, 12) as $month)
                <th>{{ str_pad((string) $month, 2, '0', STR_PAD_LEFT) }}</th>
            @endforeach
        </tr>
        </thead>
        <tbody>
        @foreach($rows as $row)
            <tr>
                <td class="name">{{ $row['plan']->name }}<br><span style="color:#777;">{{ $row['plan']->asset?->name ?? '—' }}</span></td>
                @foreach(range(1, 12) as $month)
                    <td class="{{ in_array($month, $row['months'], true) ? 'due' : '' }}">{{ in_array($month, $row['months'], true) ? '●' : '' }}</td>
                @endforeach
            </tr>
        @endforeach
        </tbody>
    </table>
@endforeach

<div class="footer">{{ __('reports.footer') }}</div>

</body>
</html>
