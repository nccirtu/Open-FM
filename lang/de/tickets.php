<?php

return [

    'model' => 'Störung',
    'models' => 'Störungen',
    'navigation' => 'Störungen',

    'fields' => [
        'ticket_number' => 'Störungs-Nr.',
        'property' => 'Objekt',
        'asset' => 'Betroffenes Asset',
        'type' => 'Art',
        'category' => 'Kategorie',
        'priority' => 'Priorität',
        'status' => 'Status',
        'title' => 'Titel',
        'description' => 'Beschreibung',
        'photos' => 'Fotos',
        'reporter' => 'Gemeldet von',
        'assignee' => 'Zugewiesen an',
        'company' => 'Dienstleister',
        'due_at' => 'Fällig am',
        'resolved_at' => 'Erledigt am',
        'resolution_notes' => 'Erledigungsvermerk',
        'created_at' => 'Gemeldet am',
        'suspension_reason' => 'Wartegrund',
        'estimate_status' => 'Kostenvoranschlag',
        'estimate_amount' => 'Angebotssumme',
        'sla' => 'SLA-Frist',
    ],

    'sections' => [
        'report' => 'Meldung',
        'processing' => 'Bearbeitung',
    ],

    'type' => [
        'fault' => 'Störung',
        'defect' => 'Mangel',
        'request' => 'Anfrage',
    ],

    'category' => [
        'electrical' => 'Elektro',
        'plumbing' => 'Sanitär',
        'hvac' => 'Heizung / Lüftung / Klima',
        'refrigeration' => 'Kältetechnik',
        'building' => 'Gebäude / Baulich',
        'safety' => 'Sicherheit / Brandschutz',
        'cleaning' => 'Reinigung',
        'other' => 'Sonstiges',
    ],

    'priority' => [
        'p1' => 'P1 — Notfall',
        'p2' => 'P2 — Dringend',
        'p3' => 'P3 — Standard',
    ],

    'status' => [
        'reported' => 'Gemeldet',
        'accepted' => 'Angenommen',
        'in_progress' => 'In Bearbeitung',
        'waiting' => 'Wartet',
        'resolved' => 'Erledigt',
        'closed' => 'Geschlossen',
    ],

    'suspension_reason' => [
        'awaiting_parts' => 'Wartet auf Ersatzteile',
        'awaiting_partner' => 'Wartet auf Dienstleister',
        'other' => 'Sonstiges',
    ],

    'estimate_status' => [
        'requested' => 'Angefordert',
        'received' => 'Erhalten',
        'approved' => 'Freigegeben',
        'rejected' => 'Abgelehnt',
    ],

    'sla' => [
        'model' => 'SLA-Regel',
        'models' => 'SLA-Regeln',
        'navigation' => 'SLA-Regeln',
        'response_hours' => 'Reaktionszeit (Stunden)',
        'response_hours_help' => 'Frist bis zur ersten Antwort eines Bearbeiters.',
        'resolution_hours' => 'Lösungszeit (Stunden)',
        'resolution_hours_help' => 'Frist bis zur Erledigung; Status „Wartet" pausiert die Uhr.',
        'priority_unique' => 'Für diese Priorität existiert bereits eine SLA-Regel.',
        'escalation' => [
            'title' => 'SLA verletzt',
            'body' => 'Die Störung ":title" hat ihre SLA-Frist überschritten (Lösungsfrist: :due).',
        ],
    ],

    'filters' => [
        'open' => 'Nur offene',
    ],

    'new' => 'NEU',

    'reporter_stores' => 'Objekte (Melder-Zuordnung)',
    'reporter_stores_help' => 'Melder sehen im Service-Portal nur diese Objekte; ohne Zuordnung alle Objekte ihres Mandanten.',

    'service_partner_help' => 'Für Dienstleister-Zugänge: Benutzer mit Rolle „Dienstleister" sehen nur die Störungen und Wartungsaufträge dieser Firma.',

    'replies' => [
        'title' => 'Verlauf & Antworten',
        'message' => 'Nachricht',
        'attachments' => 'Anhänge',
        'internal_note' => 'Interne Notiz',
        'internal_note_help' => 'Interne Notizen sind für Melder im Service-Portal nicht sichtbar.',
        'author' => 'Von',
        'created_at' => 'Datum',
        'create' => 'Antwort hinzufügen',
    ],

    'activities' => [
        'title' => 'Aktivitäten',
    ],

    'timeline' => [
        'automation' => 'Automation ":rule" wurde ausgeführt.',
    ],

    'actions' => [
        'label' => 'Aktionen',
    ],

    'process' => [
        'action' => 'Störung bearbeiten',
        'heading' => 'Störung bearbeiten',
        'submit' => 'Speichern',
    ],

    'stats' => [
        'open' => 'Offene Störungen',
        'p1_open' => 'Offene Notfälle (P1)',
        'overdue' => 'Überfällig',
        'resolved_30d' => 'Erledigt (30 Tage)',
    ],

    'automations' => [
        'model' => 'Automationsregel',
        'models' => 'Automationsregeln',
        'navigation' => 'Automationen',
        'name' => 'Name',
        'is_active' => 'Aktiv',
        'event' => 'Ereignis',
        'events' => [
            'created' => 'Ticket gemeldet',
            'status_changed' => 'Status geändert',
            'assigned' => 'Ticket zugewiesen',
            'reply_added' => 'Antwort hinzugefügt',
        ],
        'conditions' => 'Bedingungen (alle müssen zutreffen)',
        'condition_field' => 'Feld',
        'condition_value' => 'Wert',
        'condition_fields' => [
            'category' => 'Kategorie',
            'priority' => 'Priorität',
            'type' => 'Art',
            'status' => 'Status',
        ],
        'actions' => 'Aktionen',
        'action_type' => 'Aktion',
        'action_value' => 'Wert',
        'action_note' => 'Notiztext',
        'action_types' => [
            'assign_to' => 'Ticket zuweisen an',
            'set_status' => 'Status setzen',
            'set_priority' => 'Priorität setzen',
            'add_internal_note' => 'Interne Notiz hinzufügen',
        ],
    ],

    'portal' => [
        'navigation' => 'Meine Meldungen',
        'report' => 'Störung melden',
        'report_for_asset' => 'Störung melden',
        'confirm' => [
            'action' => 'Erledigung bestätigen',
            'heading' => 'Erledigung bestätigen',
            'submit' => 'Bestätigen & schließen',
            'rating' => 'Zufriedenheit',
            'rating_help' => 'Wie zufrieden sind Sie mit der Erledigung? (optional)',
        ],
        'reopen' => [
            'action' => 'Wieder öffnen',
            'heading' => 'Meldung wieder öffnen',
            'submit' => 'Wieder öffnen',
            'reason' => 'Was ist noch nicht in Ordnung?',
            'reply_prefix' => 'Meldung wieder geöffnet:',
        ],
    ],

    'notifications' => [
        'assigned' => [
            'title' => 'Störung zugewiesen',
            'body' => 'Ihnen wurde die Störung ":title" (:property) zugewiesen.',
        ],
        'reply' => [
            'title' => 'Neue Antwort',
            'body' => ':author hat auf die Störung ":title" geantwortet.',
        ],
        'status' => [
            'title' => 'Status Ihrer Meldung geändert',
            'body' => 'Ihre Meldung ":title" hat jetzt den Status „:status".',
        ],
    ],

];
