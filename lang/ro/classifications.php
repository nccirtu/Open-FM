<?php

return [
    'import' => [
        'label' => 'Șablon de clasificare',
        'help' => 'Atribuie categorii de assets și centre de cost dintr-o schemă fixă (de ex. DIN 276) și creează câmpuri suplimentare potrivite pentru fiecare tip de asset (de ex. termene de verificare pentru stingătoare, ascensoare). Nicio selecție = poate fi importat manual ulterior.',
        'none' => 'Fără șablon',
    ],
    'templates' => [
        'din276' => 'DIN 276 (Germania)',
    ],
    'asset_categories' => [
        'navigation' => 'Categorii de assets',
        'title' => 'Categorii de assets',
        'asset_type' => 'Tip de asset',
        'category' => 'Categorie',
        'unassigned' => 'Fără categorie',
    ],
    'cost_centers' => [
        'navigation' => 'Centre de cost',
        'title' => 'Centre de cost',
        'code' => 'Cod',
        'name' => 'Denumire',
        'parent' => 'Superior',
        'empty' => 'Nu au fost importate încă centre de cost.',
    ],
    'fields' => [
        'code' => 'Cod',
        'name' => 'Denumire',
    ],
    'actions' => [
        'create' => 'Nou',
        'edit' => 'Editează',
        'import' => 'Importă șablon',
        'imported' => ':count înregistrări importate.',
    ],
];
