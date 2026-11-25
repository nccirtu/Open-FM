<?php

return [

    'navigation_group' => 'Manutenzione e ispezioni',

    'plans' => [
        'model' => 'Piano di manutenzione',
        'models' => 'Piani di manutenzione',
        'navigation' => 'Piani di manutenzione',
        'generate_order' => 'Genera ordine',
        'order_created' => 'Ordine di manutenzione generato',
        'order_exists' => 'Per questa scadenza esiste già un ordine',
    ],

    'orders' => [
        'model' => 'Ordine di manutenzione',
        'models' => 'Ordini di manutenzione',
        'navigation' => 'Ordini di manutenzione',
        'filters' => [
            'overdue' => 'Solo scaduti',
        ],
        'complete' => [
            'action' => 'Completa',
            'heading' => 'Completa ordine',
            'submit' => 'Completa',
            'tasks' => 'Punti di controllo eseguiti',
            'tasks_help' => 'I punti spuntati vengono salvati sull\'ordine in modo immodificabile a fini di audit.',
            'protocol' => 'Verbale di verifica/manutenzione',
            'protocol_help' => 'Documenti probatori (PDF, foto) — archiviazione a prova di audit sull\'ordine.',
            'defect_found' => 'Difetto riscontrato',
            'defect_found_help' => 'Genera automaticamente una segnalazione di difetto sull\'asset interessato.',
            'defect_title' => 'Titolo del difetto',
            'defect_description' => 'Descrizione del difetto',
            'defect_source' => 'Riscontrato durante: :plan (:date)',
            'done' => 'Ordine completato',
        ],
    ],

    'fields' => [
        'asset' => 'Asset',
        'name' => 'Denominazione',
        'name_placeholder' => 'es. verifica DGUV V3',
        'type' => 'Tipo',
        'legal_basis' => 'Base giuridica',
        'legal_basis_placeholder' => 'es. DGUV V3, BetrSichV, TrinkwV',
        'interval' => 'Intervallo',
        'interval_value' => 'Intervallo (quantità)',
        'interval_unit' => 'Intervallo (unità)',
        'next_due_at' => 'Prossima scadenza',
        'lead_time_days' => 'Anticipo (giorni)',
        'lead_time_days_help' => 'Numero di giorni prima della scadenza in cui l\'ordine viene generato automaticamente.',
        'inspection_company' => 'Organismo di ispezione',
        'tasks' => 'Punti di controllo (checklist)',
        'tasks_help' => 'Questi punti compaiono come checklist nella finestra di completamento di ogni ordine.',
        'tasks_add' => 'Aggiungi punto di controllo',
        'is_active' => 'Attivo',
        'notes' => 'Note',
        'due_at' => 'Scadenza il',
        'status' => 'Stato',
        'completed_at' => 'Completato il',
        'completed_by' => 'Completato da',
    ],

    'type' => [
        'maintenance' => 'Manutenzione',
        'inspection' => 'Ispezione obbligatoria',
    ],

    'interval_unit' => [
        'days' => 'Giorni',
        'weeks' => 'Settimane',
        'months' => 'Mesi',
        'years' => 'Anni',
    ],

    'order_status' => [
        'open' => 'Aperto',
        'completed' => 'Completato',
        'skipped' => 'Saltato',
    ],

    'catalogs' => [
        'import_label' => 'Importa catalogo di manutenzione',
        'import_help' => 'Importa obblighi di verifica e manutenzioni specifici per Paese come modelli per tipo di asset.',
        'import_submit' => 'Importa',
        'imported' => ':count modelli importati.',
        'import_none' => 'Nessuna importazione',
        'countries' => [
            'de' => 'Germania',
            'fr' => 'Francia',
            'pl' => 'Polonia',
        ],
    ],

    'templates' => [
        'model' => 'Modello del catalogo di manutenzione',
        'models' => 'Catalogo di manutenzione',
        'navigation' => 'Catalogo di manutenzione',
        'asset_type' => 'Tipo di asset',
        'country' => 'Paese',
        'plans_count' => 'Piani generati',
    ],

    'stats' => [
        'overdue' => 'Ispezioni/manutenzioni scadute',
        'due_30d' => 'In scadenza entro 30 giorni',
        'open' => 'Ordini aperti',
        'active_plans' => 'Piani attivi',
    ],

];
