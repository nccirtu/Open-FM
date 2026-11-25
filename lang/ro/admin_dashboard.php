<?php

return [

    'system' => [
        'heading' => 'Starea sistemului',
        'pending_jobs' => 'Joburi în așteptare',
        'queue_connection' => 'Coadă: :connection',
        'failed_jobs' => 'Joburi eșuate',
        'failed_jobs_none' => 'Totul în regulă',
        'failed_jobs_some' => 'Verificați cu „php artisan queue:failed”',
        'disk_free' => 'Spațiu liber pe disc',
        'disk_description' => ':percent% din :total liber',
        'environment' => 'Mediu',
        'environment_description' => 'PHP :php · Laravel :laravel · Filament :filament',
        'debug_warning' => 'Modul de depanare este activ în producție',
    ],

    'plugins' => [
        'heading' => 'Pluginuri',
        'missing' => 'Pachete lipsă: :plugins. Rulați „php artisan plugins:restore” pe server.',
        'registry_version' => 'Registru: :version',
    ],

    'organizations' => [
        'heading' => 'Organizații',
        'model' => 'Organizație',
        'properties' => 'Imobile',
        'assets' => 'Echipamente',
        'open_tickets' => 'Tichete deschise',
        'last_change' => 'Ultima modificare',
    ],

];
