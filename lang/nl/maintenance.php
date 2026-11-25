<?php

return [

    'navigation_group' => 'Onderhoud & keuring',

    'plans' => [
        'model' => 'Onderhoudsplan',
        'models' => 'Onderhoudsplannen',
        'navigation' => 'Onderhoudsplannen',
        'generate_order' => 'Order aanmaken',
        'order_created' => 'Onderhoudsorder aangemaakt',
        'order_exists' => 'Voor deze vervaldatum bestaat al een order',
    ],

    'orders' => [
        'model' => 'Onderhoudsorder',
        'models' => 'Onderhoudsorders',
        'navigation' => 'Onderhoudsorders',
        'filters' => [
            'overdue' => 'Alleen achterstallige',
        ],
        'complete' => [
            'action' => 'Afronden',
            'heading' => 'Order afronden',
            'submit' => 'Afronden',
            'tasks' => 'Uitgevoerde controlepunten',
            'tasks_help' => 'Afgevinkte punten worden auditbestendig bij de order opgeslagen.',
            'protocol' => 'Keurings-/onderhoudsrapport',
            'protocol_help' => 'Bewijsdocumenten (PDF, foto\'s) — auditbestendige opslag bij de order.',
            'defect_found' => 'Gebrek vastgesteld',
            'defect_found_help' => 'Maakt automatisch een gebrekmelding aan bij het betreffende asset.',
            'defect_title' => 'Titel van het gebrek',
            'defect_description' => 'Beschrijving van het gebrek',
            'defect_source' => 'Vastgesteld bij: :plan (:date)',
            'done' => 'Order afgerond',
        ],
    ],

    'fields' => [
        'asset' => 'Asset',
        'name' => 'Omschrijving',
        'name_placeholder' => 'bijv. DGUV V3-keuring',
        'type' => 'Soort',
        'legal_basis' => 'Wettelijke grondslag',
        'legal_basis_placeholder' => 'bijv. DGUV V3, BetrSichV, TrinkwV',
        'interval' => 'Interval',
        'interval_value' => 'Interval (aantal)',
        'interval_unit' => 'Interval (eenheid)',
        'next_due_at' => 'Volgende vervaldatum',
        'lead_time_days' => 'Doorlooptijd (dagen)',
        'lead_time_days_help' => 'Zoveel dagen vóór de vervaldatum wordt de order automatisch aangemaakt.',
        'inspection_company' => 'Keuringsbedrijf',
        'tasks' => 'Controlepunten (checklist)',
        'tasks_help' => 'Deze punten verschijnen als checklist in het afrondvenster van elke order.',
        'tasks_add' => 'Controlepunt toevoegen',
        'is_active' => 'Actief',
        'notes' => 'Opmerkingen',
        'due_at' => 'Vervalt op',
        'status' => 'Status',
        'completed_at' => 'Afgerond op',
        'completed_by' => 'Afgerond door',
    ],

    'type' => [
        'maintenance' => 'Onderhoud',
        'inspection' => 'Wettelijke keuring',
    ],

    'interval_unit' => [
        'days' => 'Dagen',
        'weeks' => 'Weken',
        'months' => 'Maanden',
        'years' => 'Jaren',
    ],

    'order_status' => [
        'open' => 'Open',
        'completed' => 'Afgerond',
        'skipped' => 'Overgeslagen',
    ],

    'catalogs' => [
        'import_label' => 'Onderhoudscatalogus importeren',
        'import_help' => 'Importeert landspecifieke keuringsverplichtingen en onderhoudsbeurten als sjablonen per asset-type.',
        'import_submit' => 'Importeren',
        'imported' => ':count sjablonen geïmporteerd.',
        'import_none' => 'Geen import',
        'countries' => [
            'de' => 'Duitsland',
            'fr' => 'Frankrijk',
            'pl' => 'Polen',
        ],
    ],

    'templates' => [
        'model' => 'Onderhoudscatalogus-sjabloon',
        'models' => 'Onderhoudscatalogus',
        'navigation' => 'Onderhoudscatalogus',
        'asset_type' => 'Asset-type',
        'country' => 'Land',
        'plans_count' => 'Aangemaakte plannen',
    ],

    'stats' => [
        'overdue' => 'Achterstallige keuringen/onderhoudsbeurten',
        'due_30d' => 'Vervalt binnen 30 dagen',
        'open' => 'Open orders',
        'active_plans' => 'Actieve plannen',
    ],

];
