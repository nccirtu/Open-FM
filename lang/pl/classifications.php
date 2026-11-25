<?php

return [
    'import' => [
        'label' => 'Szablon klasyfikacji',
        'help' => 'Przypisuje kategorie assetów i miejsca powstawania kosztów według stałego schematu (np. DIN 276) i tworzy pasujące pola dodatkowe dla typów assetów (np. terminy przeglądów gaśnic, wind). Brak wyboru = możliwość ręcznego importu później.',
        'none' => 'Bez szablonu',
    ],
    'templates' => [
        'din276' => 'DIN 276 (Niemcy)',
    ],
    'asset_categories' => [
        'navigation' => 'Kategorie assetów',
        'title' => 'Kategorie assetów',
        'asset_type' => 'Typ assetu',
        'category' => 'Kategoria',
        'unassigned' => 'Bez kategorii',
    ],
    'cost_centers' => [
        'navigation' => 'Miejsca powstawania kosztów',
        'title' => 'Miejsca powstawania kosztów',
        'code' => 'Kod',
        'name' => 'Nazwa',
        'parent' => 'Nadrzędne',
        'empty' => 'Nie zaimportowano jeszcze miejsc powstawania kosztów.',
    ],
    'fields' => [
        'code' => 'Kod',
        'name' => 'Nazwa',
    ],
    'actions' => [
        'create' => 'Nowy',
        'edit' => 'Edytuj',
        'import' => 'Importuj szablon',
        'imported' => 'Zaimportowano :count pozycji.',
    ],
];
