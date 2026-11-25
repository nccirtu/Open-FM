<?php

return [
    'import' => [
        'label' => 'Classification template',
        'help' => 'Assigns asset categories and cost centers from a fixed scheme (e.g. DIN 276) and creates matching custom fields per asset type (e.g. inspection due dates for fire extinguishers, elevators). No selection = importable manually later.',
        'none' => 'No template',
    ],
    'templates' => [
        'din276' => 'DIN 276 (Germany)',
    ],
    'asset_categories' => [
        'navigation' => 'Asset categories',
        'title' => 'Asset categories',
        'asset_type' => 'Asset type',
        'category' => 'Category',
        'unassigned' => 'No category',
    ],
    'cost_centers' => [
        'navigation' => 'Cost centers',
        'title' => 'Cost centers',
        'code' => 'Code',
        'name' => 'Name',
        'parent' => 'Parent',
        'empty' => 'No cost centers imported yet.',
    ],
    'fields' => [
        'code' => 'Code',
        'name' => 'Name',
    ],
    'actions' => [
        'create' => 'New',
        'edit' => 'Edit',
        'import' => 'Import template',
        'imported' => ':count entries imported.',
    ],
];
