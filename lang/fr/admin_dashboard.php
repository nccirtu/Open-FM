<?php

return [

    'system' => [
        'heading' => 'État du système',
        'pending_jobs' => 'Tâches en attente',
        'queue_connection' => 'File d’attente : :connection',
        'failed_jobs' => 'Tâches échouées',
        'failed_jobs_none' => 'Tout est en ordre',
        'failed_jobs_some' => 'Vérifier avec « php artisan queue:failed »',
        'disk_free' => 'Espace disque libre',
        'disk_description' => ':percent % de :total libres',
        'environment' => 'Environnement',
        'environment_description' => 'PHP :php · Laravel :laravel · Filament :filament',
        'debug_warning' => 'Mode débogage actif en production',
    ],

    'plugins' => [
        'heading' => 'Plugins',
        'missing' => 'Paquets manquants : :plugins. Exécutez « php artisan plugins:restore » sur le serveur.',
        'registry_version' => 'Registre : :version',
    ],

    'organizations' => [
        'heading' => 'Organisations',
        'model' => 'Organisation',
        'properties' => 'Biens immobiliers',
        'assets' => 'Équipements',
        'open_tickets' => 'Tickets ouverts',
        'last_change' => 'Dernière modification',
    ],

];
