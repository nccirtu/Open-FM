<?php

return [
    'import' => [
        'label' => 'Klassifikations-Vorlage',
        'help' => 'Ordnet Asset-Kategorien und Kostenstellen aus einem festen Schema zu (z. B. DIN 276) und legt dazu passende Zusatzfelder je Asset-Typ an (z. B. Prüffristen für Feuerlöscher, Aufzüge). Keine Auswahl = später manuell importierbar.',
        'none' => 'Keine Vorlage',
    ],
    'templates' => [
        'din276' => 'DIN 276 (Deutschland)',
    ],
    'asset_categories' => [
        'navigation' => 'Asset-Kategorien',
        'title' => 'Asset-Kategorien',
        'asset_type' => 'Asset-Typ',
        'category' => 'Kategorie',
        'unassigned' => 'Keine Kategorie',
    ],
    'cost_centers' => [
        'navigation' => 'Kostenstellen',
        'title' => 'Kostenstellen',
        'code' => 'Code',
        'name' => 'Bezeichnung',
        'parent' => 'Übergeordnet',
        'empty' => 'Noch keine Kostenstellen importiert.',
    ],
    'fields' => [
        'code' => 'Code',
        'name' => 'Bezeichnung',
    ],
    'actions' => [
        'create' => 'Neu',
        'edit' => 'Bearbeiten',
        'import' => 'Vorlage importieren',
        'imported' => ':count Einträge importiert.',
    ],
];
