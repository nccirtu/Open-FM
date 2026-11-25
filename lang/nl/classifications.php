<?php

return [
    'import' => [
        'label' => 'Classificatiesjabloon',
        'help' => 'Wijst assetcategorieën en kostenplaatsen toe op basis van een vast schema (bijv. DIN 276) en maakt bijpassende extra velden per assettype aan (bijv. keuringstermijnen voor brandblussers, liften). Geen keuze = later handmatig te importeren.',
        'none' => 'Geen sjabloon',
    ],
    'templates' => [
        'din276' => 'DIN 276 (Duitsland)',
    ],
    'asset_categories' => [
        'navigation' => 'Assetcategorieën',
        'title' => 'Assetcategorieën',
        'asset_type' => 'Assettype',
        'category' => 'Categorie',
        'unassigned' => 'Geen categorie',
    ],
    'cost_centers' => [
        'navigation' => 'Kostenplaatsen',
        'title' => 'Kostenplaatsen',
        'code' => 'Code',
        'name' => 'Naam',
        'parent' => 'Bovenliggend',
        'empty' => 'Nog geen kostenplaatsen geïmporteerd.',
    ],
    'fields' => [
        'code' => 'Code',
        'name' => 'Naam',
    ],
    'actions' => [
        'create' => 'Nieuw',
        'edit' => 'Bewerken',
        'import' => 'Sjabloon importeren',
        'imported' => ':count items geïmporteerd.',
    ],
];
