<?php

return [

    'system' => [
        'heading' => 'Stan systemu',
        'pending_jobs' => 'Oczekujące zadania',
        'queue_connection' => 'Kolejka: :connection',
        'failed_jobs' => 'Nieudane zadania',
        'failed_jobs_none' => 'Wszystko w porządku',
        'failed_jobs_some' => 'Sprawdź poleceniem „php artisan queue:failed”',
        'disk_free' => 'Wolne miejsce na dysku',
        'disk_description' => ':percent% z :total wolne',
        'environment' => 'Środowisko',
        'environment_description' => 'PHP :php · Laravel :laravel · Filament :filament',
        'debug_warning' => 'Tryb debugowania włączony w produkcji',
    ],

    'plugins' => [
        'heading' => 'Wtyczki',
        'missing' => 'Brakujące pakiety: :plugins. Uruchom na serwerze „php artisan plugins:restore”.',
        'registry_version' => 'Rejestr: :version',
    ],

    'organizations' => [
        'heading' => 'Organizacje',
        'model' => 'Organizacja',
        'properties' => 'Obiekty',
        'assets' => 'Urządzenia',
        'open_tickets' => 'Otwarte zgłoszenia',
        'last_change' => 'Ostatnia zmiana',
    ],

];
