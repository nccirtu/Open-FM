<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ __('reports.dossier.title') }} {{ $property->property_number }}</title>
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
        h3 { font-size: 11px; margin: 12px 0 4px 0; }
        table.list { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.list th, table.list td { border: 1px solid #ccc; padding: 4px 5px; text-align: left; vertical-align: top; }
        table.list th { background-color: #f2f2f2; font-weight: bold; }
        .empty-hint { color: #888; font-style: italic; }
        .badge-danger { color: #b91c1c; font-weight: bold; }
        .badge-ok { color: #15803d; }
        .footer { margin-top: 25px; font-size: 8px; color: #999; }
    </style>
</head>
<body>

<div class="header">
    <span class="header-title">{{ __('reports.dossier.title') }}</span><br>
    <span class="header-sub">{{ $property->property_number }} {{ $property->city }} — {{ __('reports.generated_at') }} {{ now()->format('d.m.Y H:i') }}</span>
</div>

<h2>{{ __('reports.dossier.master_data') }}</h2>
<table class="stammdaten">
    <tr>
        <td class="label">{{ __('property.fields.property_number') }}</td>
        <td>{{ $property->property_number }}</td>
        <td class="label">{{ __('property.fields.energy_rating') }}</td>
        <td>{{ $property->energy_rating?->value ?? '—' }}{{ $property->energy_cert_valid_until ? ' ('.__('property.fields.energy_cert_valid_until').' '.$property->energy_cert_valid_until->format('d.m.Y').')' : '' }}</td>
    </tr>
    <tr>
        <td class="label">Adresse</td>
        <td>{{ trim(($property->street ?? '').', '.($property->zip_code ?? '').' '.($property->city ?? ''), ', ') }}</td>
        <td class="label">Land</td>
        <td>{{ $property->country ?? '—' }}</td>
    </tr>
    <tr>
        <td class="label">{{ __('property.fields.gross_area') }}</td>
        <td>{{ $property->gross_area !== null ? number_format((float) $property->gross_area, 2, ',', '.') : '—' }}</td>
        <td class="label">{{ __('property.fields.net_area') }}</td>
        <td>{{ $property->net_area !== null ? number_format((float) $property->net_area, 2, ',', '.') : '—' }}</td>
    </tr>
    <tr>
        <td class="label">{{ __('property.fields.cleanable_area') }}</td>
        <td>{{ $property->cleanable_area !== null ? number_format((float) $property->cleanable_area, 2, ',', '.') : '—' }}</td>
        <td class="label">{{ __('property.fields.heated_volume') }}</td>
        <td>{{ $property->heated_volume !== null ? number_format((float) $property->heated_volume, 2, ',', '.') : '—' }}</td>
    </tr>
</table>

<h2>{{ __('reports.dossier.room_book') }}</h2>
@if(empty($structureRows))
    <p class="empty-hint">{{ __('reports.dossier.no_rooms') }}</p>
@else
    <table class="list">
        <thead>
        <tr>
            <th>{{ __('property.structure.name') }}</th>
            <th>{{ __('property.structure.type') }}</th>
            <th>{{ __('property.structure.room_use') }}</th>
            <th>{{ __('property.structure.area') }}</th>
            <th>{{ __('property.structure.heated') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach($structureRows as $row)
            @php($node = $row['node'])
            <tr>
                <td style="padding-left: {{ 4 + $row['depth'] * 12 }}px">{{ $node->name }}</td>
                <td>{{ $node->type->getLabel() }}</td>
                <td>{{ $node->room_use?->getLabel() ?? '—' }}</td>
                <td>{{ $node->area !== null ? number_format((float) $node->area, 2, ',', '.') : '—' }}</td>
                <td>{{ $node->heated === null ? '—' : ($node->heated ? __('reports.yes') : __('reports.no')) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

<h2>{{ __('reports.dossier.assets') }}</h2>
@if($assetsByCategory->isEmpty())
    <p class="empty-hint">{{ __('reports.dossier.no_assets') }}</p>
@else
    @foreach($assetsByCategory as $category => $assets)
        <h3>{{ $category }}</h3>
        <table class="list">
            <thead>
            <tr>
                <th>Name</th>
                <th>{{ __('maintenance.templates.asset_type') }}</th>
                <th>{{ __('assets.fields.location') }}</th>
                <th>{{ __('assets.fields.condition') }}</th>
                <th>{{ __('assets.fields.service_status') }}</th>
                <th>{{ __('assets.fields.warranty_until') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($assets as $asset)
                <tr>
                    <td>{{ $asset->name }}</td>
                    <td>{{ $asset->assetType?->name ?? '—' }}</td>
                    <td>{{ $asset->structureNode?->name ?? '—' }}</td>
                    <td>{{ $asset->condition?->getLabel() ?? '—' }}</td>
                    <td>{{ $asset->service_status?->getLabel() ?? '—' }}</td>
                    <td class="{{ $asset->warrantyColor() === 'danger' ? 'badge-danger' : '' }}">{{ $asset->warranty_until?->format('d.m.Y') ?? '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endforeach
@endif

<h2>{{ __('reports.dossier.maintenance') }}</h2>
@if($maintenancePlans->isEmpty())
    <p class="empty-hint">{{ __('reports.dossier.no_maintenance') }}</p>
@else
    <table class="list">
        <thead>
        <tr>
            <th>{{ __('maintenance.fields.name') }}</th>
            <th>{{ __('maintenance.fields.asset') }}</th>
            <th>{{ __('maintenance.fields.legal_basis') }}</th>
            <th>{{ __('maintenance.fields.interval') }}</th>
            <th>{{ __('maintenance.fields.next_due_at') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach($maintenancePlans as $plan)
            <tr>
                <td>{{ $plan->name }}</td>
                <td>{{ $plan->asset?->name ?? '—' }}</td>
                <td>{{ $plan->legal_basis ?? '—' }}</td>
                <td>{{ $plan->interval_value }} {{ $plan->interval_unit->getLabel() }}</td>
                <td class="{{ $plan->next_due_at->isPast() ? 'badge-danger' : 'badge-ok' }}">{{ $plan->next_due_at->format('d.m.Y') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

<h2>{{ __('reports.dossier.open_tickets') }}</h2>
@if($openTickets->isEmpty())
    <p class="empty-hint">{{ __('reports.dossier.no_tickets') }}</p>
@else
    <table class="list">
        <thead>
        <tr>
            <th>#</th>
            <th>{{ __('tickets.fields.title') }}</th>
            <th>{{ __('tickets.fields.priority') }}</th>
            <th>{{ __('tickets.fields.status') }}</th>
            <th>{{ __('tickets.fields.created_at') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach($openTickets as $ticket)
            <tr>
                <td>{{ $ticket->ticket_number ?? '—' }}</td>
                <td>{{ $ticket->title }}</td>
                <td>{{ $ticket->priority?->getLabel() ?? '—' }}</td>
                <td>{{ $ticket->status->getLabel() }}</td>
                <td>{{ $ticket->created_at->format('d.m.Y') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

<h2>{{ __('reports.dossier.contracts') }}</h2>
@if($property->contracts->isEmpty())
    <p class="empty-hint">{{ __('reports.dossier.no_contracts') }}</p>
@else
    <table class="list">
        <thead>
        <tr>
            <th>{{ __('contracts.fields.name') }}</th>
            <th>{{ __('contracts.fields.partner_name') }}</th>
            <th>{{ __('contracts.fields.type') }}</th>
            <th>{{ __('contracts.fields.end_date') }}</th>
            <th>{{ __('contracts.fields.notice_deadline') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach($property->contracts as $contract)
            <tr>
                <td>{{ $contract->name }}</td>
                <td>{{ $contract->partner_name }}</td>
                <td>{{ $contract->type->getLabel() }}</td>
                <td>{{ $contract->end_date?->format('d.m.Y') ?? __('contracts.fields.open_ended') }}</td>
                <td>{{ $contract->noticeDeadline()?->format('d.m.Y') ?? '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

<div class="footer">{{ __('reports.footer') }}</div>

</body>
</html>
