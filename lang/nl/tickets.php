<?php

return [

    'model' => 'Storing',
    'models' => 'Storingen',
    'navigation' => 'Storingen',

    'fields' => [
        'ticket_number' => 'Storingsnr.',
        'property' => 'Object',
        'asset' => 'Betrokken asset',
        'type' => 'Soort',
        'category' => 'Categorie',
        'priority' => 'Prioriteit',
        'status' => 'Status',
        'title' => 'Titel',
        'description' => 'Beschrijving',
        'photos' => 'Foto\'s',
        'reporter' => 'Gemeld door',
        'assignee' => 'Toegewezen aan',
        'company' => 'Dienstverlener',
        'due_at' => 'Vervalt op',
        'resolved_at' => 'Opgelost op',
        'resolution_notes' => 'Afhandelingsnotitie',
        'created_at' => 'Gemeld op',
        'suspension_reason' => 'Wachtreden',
        'estimate_status' => 'Offerte',
        'estimate_amount' => 'Offertebedrag',
        'sla' => 'SLA-termijn',
    ],

    'sections' => [
        'report' => 'Melding',
        'processing' => 'Afhandeling',
    ],

    'type' => [
        'fault' => 'Storing',
        'defect' => 'Gebrek',
        'request' => 'Verzoek',
    ],

    'category' => [
        'electrical' => 'Elektra',
        'plumbing' => 'Sanitair',
        'hvac' => 'Verwarming / ventilatie / airco',
        'refrigeration' => 'Koeltechniek',
        'building' => 'Gebouw / bouwkundig',
        'safety' => 'Veiligheid / brandveiligheid',
        'cleaning' => 'Schoonmaak',
        'other' => 'Overig',
    ],

    'priority' => [
        'p1' => 'P1 — Noodgeval',
        'p2' => 'P2 — Urgent',
        'p3' => 'P3 — Standaard',
    ],

    'status' => [
        'reported' => 'Gemeld',
        'accepted' => 'Geaccepteerd',
        'in_progress' => 'In behandeling',
        'waiting' => 'In afwachting',
        'resolved' => 'Opgelost',
        'closed' => 'Gesloten',
    ],

    'suspension_reason' => [
        'awaiting_parts' => 'Wacht op onderdelen',
        'awaiting_partner' => 'Wacht op dienstverlener',
        'other' => 'Overig',
    ],

    'estimate_status' => [
        'requested' => 'Aangevraagd',
        'received' => 'Ontvangen',
        'approved' => 'Goedgekeurd',
        'rejected' => 'Afgewezen',
    ],

    'sla' => [
        'model' => 'SLA-regel',
        'models' => 'SLA-regels',
        'navigation' => 'SLA-regels',
        'response_hours' => 'Reactietijd (uren)',
        'response_hours_help' => 'Termijn tot de eerste reactie van een behandelaar.',
        'resolution_hours' => 'Oplostijd (uren)',
        'resolution_hours_help' => 'Termijn tot afhandeling; de status "In afwachting" pauzeert de klok.',
        'priority_unique' => 'Voor deze prioriteit bestaat al een SLA-regel.',
        'escalation' => [
            'title' => 'SLA geschonden',
            'body' => 'De storing ":title" heeft de SLA-termijn overschreden (oplostermijn: :due).',
        ],
    ],

    'filters' => [
        'open' => 'Alleen open',
    ],

    'new' => 'NIEUW',

    'reporter_stores' => 'Objecten (meldertoewijzing)',
    'reporter_stores_help' => 'Melders zien in het serviceportaal alleen deze objecten; zonder toewijzing alle objecten van hun tenant.',

    'service_partner_help' => 'Voor dienstverlenerstoegangen: gebruikers met de rol "Dienstverlener" zien alleen de storingen en onderhoudsorders van dit bedrijf.',

    'replies' => [
        'title' => 'Verloop & reacties',
        'message' => 'Bericht',
        'attachments' => 'Bijlagen',
        'internal_note' => 'Interne notitie',
        'internal_note_help' => 'Interne notities zijn voor melders in het serviceportaal niet zichtbaar.',
        'author' => 'Van',
        'created_at' => 'Datum',
        'create' => 'Reactie toevoegen',
    ],

    'activities' => [
        'title' => 'Activiteiten',
    ],

    'timeline' => [
        'automation' => 'Automatisering ":rule" is uitgevoerd.',
    ],

    'actions' => [
        'label' => 'Acties',
    ],

    'process' => [
        'action' => 'Storing afhandelen',
        'heading' => 'Storing afhandelen',
        'submit' => 'Opslaan',
    ],

    'stats' => [
        'open' => 'Open storingen',
        'p1_open' => 'Open noodgevallen (P1)',
        'overdue' => 'Achterstallig',
        'resolved_30d' => 'Opgelost (30 dagen)',
    ],

    'automations' => [
        'model' => 'Automatiseringsregel',
        'models' => 'Automatiseringsregels',
        'navigation' => 'Automatiseringen',
        'name' => 'Naam',
        'is_active' => 'Actief',
        'event' => 'Gebeurtenis',
        'events' => [
            'created' => 'Ticket gemeld',
            'status_changed' => 'Status gewijzigd',
            'assigned' => 'Ticket toegewezen',
            'reply_added' => 'Reactie toegevoegd',
        ],
        'conditions' => 'Voorwaarden (alle moeten van toepassing zijn)',
        'condition_field' => 'Veld',
        'condition_value' => 'Waarde',
        'condition_fields' => [
            'category' => 'Categorie',
            'priority' => 'Prioriteit',
            'type' => 'Soort',
            'status' => 'Status',
        ],
        'actions' => 'Acties',
        'action_type' => 'Actie',
        'action_value' => 'Waarde',
        'action_note' => 'Notitietekst',
        'action_types' => [
            'assign_to' => 'Ticket toewijzen aan',
            'set_status' => 'Status instellen',
            'set_priority' => 'Prioriteit instellen',
            'add_internal_note' => 'Interne notitie toevoegen',
        ],
    ],

    'portal' => [
        'navigation' => 'Mijn meldingen',
        'report' => 'Storing melden',
        'report_for_asset' => 'Storing melden',
        'confirm' => [
            'action' => 'Afhandeling bevestigen',
            'heading' => 'Afhandeling bevestigen',
            'submit' => 'Bevestigen & sluiten',
            'rating' => 'Tevredenheid',
            'rating_help' => 'Hoe tevreden bent u met de afhandeling? (optioneel)',
        ],
        'reopen' => [
            'action' => 'Opnieuw openen',
            'heading' => 'Melding opnieuw openen',
            'submit' => 'Opnieuw openen',
            'reason' => 'Wat is er nog niet in orde?',
            'reply_prefix' => 'Melding opnieuw geopend:',
        ],
    ],

    'notifications' => [
        'assigned' => [
            'title' => 'Storing toegewezen',
            'body' => 'De storing ":title" (:property) is aan u toegewezen.',
        ],
        'reply' => [
            'title' => 'Nieuwe reactie',
            'body' => ':author heeft gereageerd op de storing ":title".',
        ],
        'status' => [
            'title' => 'Status van uw melding gewijzigd',
            'body' => 'Uw melding ":title" heeft nu de status ":status".',
        ],
    ],

];
