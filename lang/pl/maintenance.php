<?php

return [

    'navigation_group' => 'Konserwacja i przeglądy',

    'plans' => [
        'model' => 'Plan konserwacji',
        'models' => 'Plany konserwacji',
        'navigation' => 'Plany konserwacji',
        'generate_order' => 'Utwórz zlecenie',
        'order_created' => 'Zlecenie konserwacji utworzone',
        'order_exists' => 'Dla tego terminu istnieje już zlecenie',
    ],

    'orders' => [
        'model' => 'Zlecenie konserwacji',
        'models' => 'Zlecenia konserwacji',
        'navigation' => 'Zlecenia konserwacji',
        'filters' => [
            'overdue' => 'Tylko zaległe',
        ],
        'complete' => [
            'action' => 'Zakończ',
            'heading' => 'Zakończ zlecenie',
            'submit' => 'Zakończ',
            'tasks' => 'Wykonane punkty kontrolne',
            'tasks_help' => 'Odhaczone punkty są zapisywane przy zleceniu w sposób umożliwiający audyt.',
            'protocol' => 'Protokół przeglądu/konserwacji',
            'protocol_help' => 'Dokumenty potwierdzające (PDF, zdjęcia) — archiwizacja przy zleceniu w sposób umożliwiający audyt.',
            'defect_found' => 'Stwierdzono wadę',
            'defect_found_help' => 'Automatycznie tworzy zgłoszenie wady przy danym assecie.',
            'defect_title' => 'Tytuł wady',
            'defect_description' => 'Opis wady',
            'defect_source' => 'Stwierdzono podczas: :plan (:date)',
            'done' => 'Zlecenie zakończone',
        ],
    ],

    'fields' => [
        'asset' => 'Asset',
        'name' => 'Nazwa',
        'name_placeholder' => 'np. przegląd DGUV V3',
        'type' => 'Rodzaj',
        'legal_basis' => 'Podstawa prawna',
        'legal_basis_placeholder' => 'np. DGUV V3, BetrSichV, TrinkwV',
        'interval' => 'Interwał',
        'interval_value' => 'Interwał (liczba)',
        'interval_unit' => 'Interwał (jednostka)',
        'next_due_at' => 'Następny termin',
        'lead_time_days' => 'Wyprzedzenie (dni)',
        'lead_time_days_help' => 'Tyle dni przed terminem zlecenie zostanie utworzone automatycznie.',
        'inspection_company' => 'Firma kontrolna',
        'tasks' => 'Punkty kontrolne (lista kontrolna)',
        'tasks_help' => 'Te punkty pojawiają się jako lista kontrolna w oknie zakończenia każdego zlecenia.',
        'tasks_add' => 'Dodaj punkt kontrolny',
        'is_active' => 'Aktywny',
        'notes' => 'Uwagi',
        'due_at' => 'Termin',
        'status' => 'Status',
        'completed_at' => 'Zakończono',
        'completed_by' => 'Zakończone przez',
    ],

    'type' => [
        'maintenance' => 'Konserwacja',
        'inspection' => 'Przegląd wymagany prawem',
    ],

    'interval_unit' => [
        'days' => 'Dni',
        'weeks' => 'Tygodnie',
        'months' => 'Miesiące',
        'years' => 'Lata',
    ],

    'order_status' => [
        'open' => 'Otwarte',
        'completed' => 'Zakończone',
        'skipped' => 'Pominięte',
    ],

    'catalogs' => [
        'import_label' => 'Importuj katalog konserwacji',
        'import_help' => 'Importuje krajowe obowiązki przeglądowe i konserwacje jako szablony dla poszczególnych typów assetów.',
        'import_submit' => 'Importuj',
        'imported' => 'Zaimportowano :count szablonów.',
        'import_none' => 'Bez importu',
        'countries' => [
            'de' => 'Niemcy',
            'fr' => 'Francja',
            'pl' => 'Polska',
        ],
    ],

    'templates' => [
        'model' => 'Szablon katalogu konserwacji',
        'models' => 'Katalog konserwacji',
        'navigation' => 'Katalog konserwacji',
        'asset_type' => 'Typ assetu',
        'country' => 'Kraj',
        'plans_count' => 'Utworzone plany',
    ],

    'stats' => [
        'overdue' => 'Zaległe przeglądy/konserwacje',
        'due_30d' => 'Termin w ciągu 30 dni',
        'open' => 'Otwarte zlecenia',
        'active_plans' => 'Aktywne plany',
    ],

];
