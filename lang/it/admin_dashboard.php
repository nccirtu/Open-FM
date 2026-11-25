<?php

return [

    'system' => [
        'heading' => 'Stato del sistema',
        'pending_jobs' => 'Job in attesa',
        'queue_connection' => 'Coda: :connection',
        'failed_jobs' => 'Job falliti',
        'failed_jobs_none' => 'Tutto in ordine',
        'failed_jobs_some' => 'Verificare con «php artisan queue:failed»',
        'disk_free' => 'Spazio su disco libero',
        'disk_description' => ':percent% di :total libero',
        'environment' => 'Ambiente',
        'environment_description' => 'PHP :php · Laravel :laravel · Filament :filament',
        'debug_warning' => 'Modalità debug attiva in produzione',
    ],

    'plugins' => [
        'heading' => 'Plugin',
        'missing' => 'Pacchetti mancanti: :plugins. Eseguire «php artisan plugins:restore» sul server.',
        'registry_version' => 'Registro: :version',
    ],

    'organizations' => [
        'heading' => 'Organizzazioni',
        'model' => 'Organizzazione',
        'properties' => 'Immobili',
        'assets' => 'Impianti',
        'open_tickets' => 'Ticket aperti',
        'last_change' => 'Ultima modifica',
    ],

];
