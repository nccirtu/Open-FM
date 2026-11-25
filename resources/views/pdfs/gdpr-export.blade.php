<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ __('gdpr.export.title') }} — {{ $user->name }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10px; color: #333; }
        @page { margin: 40px 25px; }
        .header { width: 100%; margin-bottom: 20px; border-bottom: 2px solid #005A9C; padding-bottom: 10px; }
        .header-title { font-size: 24px; font-weight: bold; color: #005A9C; }
        .header-sub { font-size: 12px; color: #666; }
        .stammdaten { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .stammdaten td { padding: 4px 5px; border: 1px solid #ddd; }
        .stammdaten .label { font-weight: bold; background-color: #f9f9f9; width: 22%; }
        h2 { font-size: 14px; color: #005A9C; padding-left: 5px; border-left: 4px solid #005A9C; margin-top: 22px; margin-bottom: 8px; }
        table.list { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.list th, table.list td { border: 1px solid #ccc; padding: 4px 5px; text-align: left; vertical-align: top; }
        table.list th { background-color: #f2f2f2; font-weight: bold; }
        .empty-hint { color: #888; font-style: italic; }
        .footer { margin-top: 25px; font-size: 8px; color: #999; }
    </style>
</head>
<body>

<div class="header">
    <span class="header-title">{{ __('gdpr.export.title') }}</span><br>
    <span class="header-sub">{{ $user->name }} ({{ $user->email }}) — {{ __('reports.generated_at') }} {{ now()->format('d.m.Y H:i') }}</span>
</div>

<h2>{{ __('gdpr.export.master_data') }}</h2>
<table class="stammdaten">
    <tr>
        <td class="label">Name</td>
        <td>{{ $user->name }}</td>
        <td class="label">E-Mail</td>
        <td>{{ $user->email }}</td>
    </tr>
    <tr>
        <td class="label">{{ __('gdpr.export.organizations') }}</td>
        <td colspan="3">{{ $user->organizations->pluck('name')->implode(', ') ?: '—' }}</td>
    </tr>
    <tr>
        <td class="label">{{ __('gdpr.export.created_at') }}</td>
        <td>{{ $user->created_at?->format('d.m.Y H:i') }}</td>
        <td class="label">{{ __('gdpr.export.email_verified_at') }}</td>
        <td>{{ $user->email_verified_at?->format('d.m.Y H:i') ?? '—' }}</td>
    </tr>
</table>

@forelse ($groups as $group)
    <h2>{{ $group['label'] }}</h2>
    <table class="list">
        <thead>
            <tr>
                @foreach (array_keys($group['records'][0]) as $column)
                    <th>{{ $column }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($group['records'] as $record)
                <tr>
                    @foreach ($record as $value)
                        <td>{{ $value }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
@empty
    <p class="empty-hint">{{ __('gdpr.export.empty') }}</p>
@endforelse

<div class="footer">{{ __('gdpr.export.footer') }}</div>

</body>
</html>
