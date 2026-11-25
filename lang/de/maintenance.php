<?php

return [

    'navigation_group' => 'Wartung & Prüfung',

    'plans' => [
        'model' => 'Wartungsplan',
        'models' => 'Wartungspläne',
        'navigation' => 'Wartungspläne',
        'generate_order' => 'Auftrag erzeugen',
        'order_created' => 'Wartungsauftrag erzeugt',
        'order_exists' => 'Für diese Fälligkeit existiert bereits ein Auftrag',
    ],

    'orders' => [
        'model' => 'Wartungsauftrag',
        'models' => 'Wartungsaufträge',
        'navigation' => 'Wartungsaufträge',
        'filters' => [
            'overdue' => 'Nur überfällige',
        ],
        'complete' => [
            'action' => 'Abschließen',
            'heading' => 'Auftrag abschließen',
            'submit' => 'Abschließen',
            'tasks' => 'Erledigte Prüfpunkte',
            'tasks_help' => 'Abgehakte Punkte werden revisionssicher am Auftrag gespeichert.',
            'protocol' => 'Prüf-/Wartungsprotokoll',
            'protocol_help' => 'Nachweisende Dokumente (PDF, Fotos) — revisionssichere Ablage am Auftrag.',
            'defect_found' => 'Mangel festgestellt',
            'defect_found_help' => 'Erzeugt automatisch eine Mangel-Meldung am betroffenen Asset.',
            'defect_title' => 'Mangel-Titel',
            'defect_description' => 'Mangel-Beschreibung',
            'defect_source' => 'Festgestellt bei: :plan (:date)',
            'done' => 'Auftrag abgeschlossen',
        ],
    ],

    'fields' => [
        'asset' => 'Asset',
        'name' => 'Bezeichnung',
        'name_placeholder' => 'z. B. DGUV V3 Prüfung',
        'type' => 'Art',
        'legal_basis' => 'Rechtsgrundlage',
        'legal_basis_placeholder' => 'z. B. DGUV V3, BetrSichV, TrinkwV',
        'interval' => 'Intervall',
        'interval_value' => 'Intervall (Anzahl)',
        'interval_unit' => 'Intervall (Einheit)',
        'next_due_at' => 'Nächste Fälligkeit',
        'lead_time_days' => 'Vorlauf (Tage)',
        'lead_time_days_help' => 'So viele Tage vor der Fälligkeit wird der Auftrag automatisch erzeugt.',
        'inspection_company' => 'Prüffirma',
        'tasks' => 'Prüfpunkte (Checkliste)',
        'tasks_help' => 'Diese Punkte erscheinen als Checkliste im Abschluss-Modal jedes Auftrags.',
        'tasks_add' => 'Prüfpunkt hinzufügen',
        'is_active' => 'Aktiv',
        'notes' => 'Bemerkungen',
        'due_at' => 'Fällig am',
        'status' => 'Status',
        'completed_at' => 'Abgeschlossen am',
        'completed_by' => 'Abgeschlossen von',
    ],

    'type' => [
        'maintenance' => 'Wartung',
        'inspection' => 'Gesetzliche Prüfung',
    ],

    'interval_unit' => [
        'days' => 'Tage',
        'weeks' => 'Wochen',
        'months' => 'Monate',
        'years' => 'Jahre',
    ],

    'order_status' => [
        'open' => 'Offen',
        'completed' => 'Abgeschlossen',
        'skipped' => 'Übersprungen',
    ],

    'catalogs' => [
        'import_label' => 'Wartungskatalog importieren',
        'import_help' => 'Importiert länderspezifische Prüfpflichten und Wartungen als Vorlagen je Asset-Typ.',
        'import_submit' => 'Importieren',
        'imported' => ':count Vorlagen importiert.',
        'import_none' => 'Kein Import',
        'countries' => [
            'de' => 'Deutschland',
            'fr' => 'Frankreich',
            'pl' => 'Polen',
        ],
    ],

    'templates' => [
        'model' => 'Wartungskatalog-Vorlage',
        'models' => 'Wartungskatalog',
        'navigation' => 'Wartungskatalog',
        'asset_type' => 'Asset-Typ',
        'country' => 'Land',
        'plans_count' => 'Erzeugte Pläne',
    ],

    'stats' => [
        'overdue' => 'Überfällige Prüfungen/Wartungen',
        'due_30d' => 'Fällig in 30 Tagen',
        'open' => 'Offene Aufträge',
        'active_plans' => 'Aktive Pläne',
    ],

];
