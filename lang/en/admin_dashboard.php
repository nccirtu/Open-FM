<?php

return [

    'system' => [
        'heading' => 'System health',
        'pending_jobs' => 'Pending jobs',
        'queue_connection' => 'Queue: :connection',
        'failed_jobs' => 'Failed jobs',
        'failed_jobs_none' => 'All good',
        'failed_jobs_some' => 'Check with “php artisan queue:failed”',
        'disk_free' => 'Free disk space',
        'disk_description' => ':percent% of :total free',
        'environment' => 'Environment',
        'environment_description' => 'PHP :php · Laravel :laravel · Filament :filament',
        'debug_warning' => 'Debug mode is on in production',
    ],

    'plugins' => [
        'heading' => 'Plugins',
        'missing' => 'Packages missing: :plugins. Run “php artisan plugins:restore” on the server.',
        'registry_version' => 'Registry: :version',
    ],

    'organizations' => [
        'heading' => 'Organisations',
        'model' => 'Organisation',
        'properties' => 'Properties',
        'assets' => 'Assets',
        'open_tickets' => 'Open tickets',
        'last_change' => 'Last change',
    ],

];
