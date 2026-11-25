<?php

return [

    'navigation_group' => 'Mentenanță & Verificare',

    'plans' => [
        'model' => 'Plan de mentenanță',
        'models' => 'Planuri de mentenanță',
        'navigation' => 'Planuri de mentenanță',
        'generate_order' => 'Generează comandă',
        'order_created' => 'Comandă de mentenanță generată',
        'order_exists' => 'Pentru această scadență există deja o comandă',
    ],

    'orders' => [
        'model' => 'Comandă de mentenanță',
        'models' => 'Comenzi de mentenanță',
        'navigation' => 'Comenzi de mentenanță',
        'filters' => [
            'overdue' => 'Doar restante',
        ],
        'complete' => [
            'action' => 'Finalizează',
            'heading' => 'Finalizează comanda',
            'submit' => 'Finalizează',
            'tasks' => 'Puncte de verificare efectuate',
            'tasks_help' => 'Punctele bifate se salvează la comandă, cu asigurarea trasabilității.',
            'protocol' => 'Proces-verbal de verificare/mentenanță',
            'protocol_help' => 'Documente doveditoare (PDF, fotografii) — arhivare cu trasabilitate la comandă.',
            'defect_found' => 'Deficiență constatată',
            'defect_found_help' => 'Generează automat o sesizare de deficiență la assetul afectat.',
            'defect_title' => 'Titlul deficienței',
            'defect_description' => 'Descrierea deficienței',
            'defect_source' => 'Constatată la: :plan (:date)',
            'done' => 'Comandă finalizată',
        ],
    ],

    'fields' => [
        'asset' => 'Asset',
        'name' => 'Denumire',
        'name_placeholder' => 'de ex. verificare DGUV V3',
        'type' => 'Tip',
        'legal_basis' => 'Temei legal',
        'legal_basis_placeholder' => 'de ex. DGUV V3, BetrSichV, TrinkwV',
        'interval' => 'Interval',
        'interval_value' => 'Interval (număr)',
        'interval_unit' => 'Interval (unitate)',
        'next_due_at' => 'Următoarea scadență',
        'lead_time_days' => 'Avans (zile)',
        'lead_time_days_help' => 'Cu atâtea zile înainte de scadență comanda se generează automat.',
        'inspection_company' => 'Firmă de inspecție',
        'tasks' => 'Puncte de verificare (listă de control)',
        'tasks_help' => 'Aceste puncte apar ca listă de control în fereastra de finalizare a fiecărei comenzi.',
        'tasks_add' => 'Adaugă punct de verificare',
        'is_active' => 'Activ',
        'notes' => 'Observații',
        'due_at' => 'Scadent la',
        'status' => 'Stare',
        'completed_at' => 'Finalizat la',
        'completed_by' => 'Finalizat de',
    ],

    'type' => [
        'maintenance' => 'Mentenanță',
        'inspection' => 'Verificare legală',
    ],

    'interval_unit' => [
        'days' => 'Zile',
        'weeks' => 'Săptămâni',
        'months' => 'Luni',
        'years' => 'Ani',
    ],

    'order_status' => [
        'open' => 'Deschisă',
        'completed' => 'Finalizată',
        'skipped' => 'Omisă',
    ],

    'catalogs' => [
        'import_label' => 'Importă catalog de mentenanță',
        'import_help' => 'Importă obligații de verificare și lucrări de mentenanță specifice fiecărei țări, ca șabloane per tip de asset.',
        'import_submit' => 'Importă',
        'imported' => ':count șabloane importate.',
        'import_none' => 'Fără import',
        'countries' => [
            'de' => 'Germania',
            'fr' => 'Franța',
            'pl' => 'Polonia',
        ],
    ],

    'templates' => [
        'model' => 'Șablon din catalogul de mentenanță',
        'models' => 'Catalog de mentenanță',
        'navigation' => 'Catalog de mentenanță',
        'asset_type' => 'Tip de asset',
        'country' => 'Țară',
        'plans_count' => 'Planuri generate',
    ],

    'stats' => [
        'overdue' => 'Verificări/mentenanțe restante',
        'due_30d' => 'Scadente în 30 de zile',
        'open' => 'Comenzi deschise',
        'active_plans' => 'Planuri active',
    ],

];
