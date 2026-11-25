<?php

return [

    'system' => [
        'heading' => 'Systemzustand',
        'pending_jobs' => 'Wartende Jobs',
        'queue_connection' => 'Warteschlange: :connection',
        'failed_jobs' => 'Fehlgeschlagene Jobs',
        'failed_jobs_none' => 'Alles in Ordnung',
        'failed_jobs_some' => 'Prüfen mit „php artisan queue:failed“',
        'disk_free' => 'Freier Speicherplatz',
        'disk_description' => ':percent % von :total frei',
        'environment' => 'Umgebung',
        'environment_description' => 'PHP :php · Laravel :laravel · Filament :filament',
        'debug_warning' => 'Debug-Modus in Produktion aktiv',
    ],

    'plugins' => [
        'heading' => 'Plugins',
        'missing' => 'Pakete fehlen: :plugins. Auf dem Server „php artisan plugins:restore“ ausführen.',
        'registry_version' => 'Register: :version',
    ],

    'organizations' => [
        'heading' => 'Mandanten',
        'model' => 'Mandant',
        'properties' => 'Objekte',
        'assets' => 'Anlagen',
        'open_tickets' => 'Offene Störungen',
        'last_change' => 'Letzte Änderung',
    ],

];
