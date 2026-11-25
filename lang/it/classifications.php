<?php

return [
    'import' => [
        'label' => 'Modello di classificazione',
        'help' => 'Assegna categorie di asset e centri di costo secondo uno schema predefinito (es. DIN 276) e crea i relativi campi aggiuntivi per tipo di asset (es. scadenze di verifica per estintori, ascensori). Nessuna selezione = importabile manualmente in seguito.',
        'none' => 'Nessun modello',
    ],
    'templates' => [
        'din276' => 'DIN 276 (Germania)',
    ],
    'asset_categories' => [
        'navigation' => 'Categorie di asset',
        'title' => 'Categorie di asset',
        'asset_type' => 'Tipo di asset',
        'category' => 'Categoria',
        'unassigned' => 'Nessuna categoria',
    ],
    'cost_centers' => [
        'navigation' => 'Centri di costo',
        'title' => 'Centri di costo',
        'code' => 'Codice',
        'name' => 'Denominazione',
        'parent' => 'Sovraordinato',
        'empty' => 'Nessun centro di costo ancora importato.',
    ],
    'fields' => [
        'code' => 'Codice',
        'name' => 'Denominazione',
    ],
    'actions' => [
        'create' => 'Nuovo',
        'edit' => 'Modifica',
        'import' => 'Importa modello',
        'imported' => ':count voci importate.',
    ],
];
