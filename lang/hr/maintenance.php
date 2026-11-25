<?php

return [

    'navigation_group' => 'Održavanje i ispitivanja',

    'plans' => [
        'model' => 'Plan održavanja',
        'models' => 'Planovi održavanja',
        'navigation' => 'Planovi održavanja',
        'generate_order' => 'Izradi nalog',
        'order_created' => 'Nalog za održavanje izrađen',
        'order_exists' => 'Za ovo dospijeće već postoji nalog',
    ],

    'orders' => [
        'model' => 'Nalog za održavanje',
        'models' => 'Nalozi za održavanje',
        'navigation' => 'Nalozi za održavanje',
        'filters' => [
            'overdue' => 'Samo u kašnjenju',
        ],
        'complete' => [
            'action' => 'Završi',
            'heading' => 'Završi nalog',
            'submit' => 'Završi',
            'tasks' => 'Obavljene kontrolne točke',
            'tasks_help' => 'Označene točke pohranjuju se uz nalog na revizijski siguran način.',
            'protocol' => 'Zapisnik o ispitivanju/održavanju',
            'protocol_help' => 'Dokazni dokumenti (PDF, fotografije) — revizijski sigurna pohrana uz nalog.',
            'defect_found' => 'Utvrđen nedostatak',
            'defect_found_help' => 'Automatski izrađuje prijavu nedostatka na pogođenom sredstvu.',
            'defect_title' => 'Naslov nedostatka',
            'defect_description' => 'Opis nedostatka',
            'defect_source' => 'Utvrđeno kod: :plan (:date)',
            'done' => 'Nalog je završen',
        ],
    ],

    'fields' => [
        'asset' => 'Sredstvo',
        'name' => 'Naziv',
        'name_placeholder' => 'npr. ispitivanje DGUV V3',
        'type' => 'Vrsta',
        'legal_basis' => 'Pravna osnova',
        'legal_basis_placeholder' => 'npr. DGUV V3, BetrSichV, TrinkwV',
        'interval' => 'Interval',
        'interval_value' => 'Interval (broj)',
        'interval_unit' => 'Interval (jedinica)',
        'next_due_at' => 'Sljedeće dospijeće',
        'lead_time_days' => 'Najava unaprijed (dana)',
        'lead_time_days_help' => 'Toliko dana prije dospijeća nalog se automatski izrađuje.',
        'inspection_company' => 'Ispitna tvrtka',
        'tasks' => 'Kontrolne točke (kontrolna lista)',
        'tasks_help' => 'Ove se točke prikazuju kao kontrolna lista u prozoru za završetak svakog naloga.',
        'tasks_add' => 'Dodaj kontrolnu točku',
        'is_active' => 'Aktivno',
        'notes' => 'Napomene',
        'due_at' => 'Dospijeće',
        'status' => 'Status',
        'completed_at' => 'Završeno',
        'completed_by' => 'Završio/la',
    ],

    'type' => [
        'maintenance' => 'Održavanje',
        'inspection' => 'Zakonsko ispitivanje',
    ],

    'interval_unit' => [
        'days' => 'Dani',
        'weeks' => 'Tjedni',
        'months' => 'Mjeseci',
        'years' => 'Godine',
    ],

    'order_status' => [
        'open' => 'Otvoren',
        'completed' => 'Završen',
        'skipped' => 'Preskočen',
    ],

    'catalogs' => [
        'import_label' => 'Uvezi katalog održavanja',
        'import_help' => 'Uvozi obveze ispitivanja i održavanja specifične za pojedinu zemlju kao predloške po tipu sredstva.',
        'import_submit' => 'Uvezi',
        'imported' => ':count predložaka uvezeno.',
        'import_none' => 'Bez uvoza',
        'countries' => [
            'de' => 'Njemačka',
            'fr' => 'Francuska',
            'pl' => 'Poljska',
        ],
    ],

    'templates' => [
        'model' => 'Predložak kataloga održavanja',
        'models' => 'Katalog održavanja',
        'navigation' => 'Katalog održavanja',
        'asset_type' => 'Tip sredstva',
        'country' => 'Zemlja',
        'plans_count' => 'Izrađeni planovi',
    ],

    'stats' => [
        'overdue' => 'Ispitivanja/održavanja u kašnjenju',
        'due_30d' => 'Dospijeva u 30 dana',
        'open' => 'Otvoreni nalozi',
        'active_plans' => 'Aktivni planovi',
    ],

];
