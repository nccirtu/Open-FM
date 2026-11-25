<?php

return [
    'import' => [
        'label' => 'Modèle de classification',
        'help' => 'Associe les catégories d\'équipements et les centres de coûts selon un référentiel établi (p. ex. DIN 276) et crée les champs additionnels correspondants par type d\'équipement (p. ex. échéances de contrôle pour extincteurs, ascenseurs). Aucune sélection = import manuel possible ultérieurement.',
        'none' => 'Aucun modèle',
    ],
    'templates' => [
        'din276' => 'DIN 276 (Allemagne)',
    ],
    'asset_categories' => [
        'navigation' => 'Catégories d\'équipements',
        'title' => 'Catégories d\'équipements',
        'asset_type' => 'Type d\'équipement',
        'category' => 'Catégorie',
        'unassigned' => 'Aucune catégorie',
    ],
    'cost_centers' => [
        'navigation' => 'Centres de coûts',
        'title' => 'Centres de coûts',
        'code' => 'Code',
        'name' => 'Libellé',
        'parent' => 'Parent',
        'empty' => 'Aucun centre de coûts importé pour l\'instant.',
    ],
    'fields' => [
        'code' => 'Code',
        'name' => 'Libellé',
    ],
    'actions' => [
        'create' => 'Nouveau',
        'edit' => 'Modifier',
        'import' => 'Importer un modèle',
        'imported' => ':count entrées importées.',
    ],
];
