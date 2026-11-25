<?php

return [

    'system' => [
        'heading' => 'Stanje sustava',
        'pending_jobs' => 'Poslovi na čekanju',
        'queue_connection' => 'Red: :connection',
        'failed_jobs' => 'Neuspjeli poslovi',
        'failed_jobs_none' => 'Sve je u redu',
        'failed_jobs_some' => 'Provjerite s „php artisan queue:failed”',
        'disk_free' => 'Slobodan prostor na disku',
        'disk_description' => ':percent % od :total slobodno',
        'environment' => 'Okruženje',
        'environment_description' => 'PHP :php · Laravel :laravel · Filament :filament',
        'debug_warning' => 'Način otklanjanja pogrešaka aktivan u produkciji',
    ],

    'plugins' => [
        'heading' => 'Dodaci',
        'missing' => 'Nedostaju paketi: :plugins. Pokrenite „php artisan plugins:restore” na poslužitelju.',
        'registry_version' => 'Registar: :version',
    ],

    'organizations' => [
        'heading' => 'Organizacije',
        'model' => 'Organizacija',
        'properties' => 'Objekti',
        'assets' => 'Oprema',
        'open_tickets' => 'Otvorene prijave',
        'last_change' => 'Zadnja promjena',
    ],

];
