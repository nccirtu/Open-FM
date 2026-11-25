<?php

return [

    'system' => [
        'heading' => 'Systeemstatus',
        'pending_jobs' => 'Wachtende jobs',
        'queue_connection' => 'Wachtrij: :connection',
        'failed_jobs' => 'Mislukte jobs',
        'failed_jobs_none' => 'Alles in orde',
        'failed_jobs_some' => 'Controleren met “php artisan queue:failed”',
        'disk_free' => 'Vrije schijfruimte',
        'disk_description' => ':percent% van :total vrij',
        'environment' => 'Omgeving',
        'environment_description' => 'PHP :php · Laravel :laravel · Filament :filament',
        'debug_warning' => 'Debugmodus actief in productie',
    ],

    'plugins' => [
        'heading' => 'Plugins',
        'missing' => 'Pakketten ontbreken: :plugins. Voer “php artisan plugins:restore” uit op de server.',
        'registry_version' => 'Register: :version',
    ],

    'organizations' => [
        'heading' => 'Organisaties',
        'model' => 'Organisatie',
        'properties' => 'Objecten',
        'assets' => 'Installaties',
        'open_tickets' => 'Open meldingen',
        'last_change' => 'Laatste wijziging',
    ],

];
