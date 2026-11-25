<?php

return [
    'marketplace' => [
        'url' => env('PLUGINS_MARKETPLACE_URL'),
        'timeout' => 15,
    ],

    'composer' => [
        'binary' => env('PLUGINS_COMPOSER_BINARY'),
        'timeout' => (int) env('PLUGINS_COMPOSER_TIMEOUT', 900),
        'working_dir' => base_path(),
    ],

    'artifacts_path' => storage_path('app/private/plugins/artifacts'),

    'dev_path' => env('PLUGINS_DEV_PATH'),

    'public_key' => env('PLUGINS_PUBLIC_KEY'),
    'require_signature' => (bool) env('PLUGINS_REQUIRE_SIGNATURE', false),

    'secret_key' => env('PLUGINS_SECRET_KEY'),

    'catalog' => [
        'structure-tree' => [
            'package' => 'cafm/structure-tree',
            'category' => 'structure',
            'icon' => 'heroicon-o-building-office-2',
            'requires' => [],
            'publish_tags' => [],
        ],
        'gantt-chart' => [
            'package' => 'cafm/gantt-chart',
            'category' => 'visualization',
            'icon' => 'heroicon-o-chart-bar-square',
            'requires' => [],
            'publish_tags' => [],
        ],
        'map-widget' => [
            'package' => 'cafm/map-widget',
            'category' => 'visualization',
            'icon' => 'heroicon-o-map',
            'requires' => [],
            'publish_tags' => [],
        ],
        'bim-viewer' => [
            'package' => 'cafm/bim-viewer',
            'category' => 'visualization',
            'icon' => 'heroicon-o-cube',
            'requires' => [],
            'publish_tags' => ['bim-viewer-assets'],
        ],
        'form-builder' => [
            'package' => 'cafm/form-builder',
            'category' => 'forms',
            'icon' => 'heroicon-o-clipboard-document-list',
            'requires' => [],
            'publish_tags' => [],
        ],
        'custom-fields' => [
            'package' => 'cafm/custom-fields',
            'category' => 'forms',
            'icon' => 'heroicon-o-adjustments-horizontal',
            'requires' => ['form-builder'],
            'publish_tags' => [],
        ],
        'receipt-capture' => [
            'package' => 'cafm/receipt-capture',
            'category' => 'capture',
            'icon' => 'heroicon-o-receipt-percent',
            'requires' => [],
            'publish_tags' => ['receipt-capture-assets'],
        ],
        'qr-code' => [
            'package' => 'cafm/qr-code',
            'category' => 'capture',
            'icon' => 'heroicon-o-qr-code',
            'requires' => [],
            'publish_tags' => [],
        ],
    ],
];
