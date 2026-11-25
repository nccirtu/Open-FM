<?php

return [
    'actions' => [
        'export' => 'DSGVO-Auskunft',
        'anonymize' => 'Anonymisieren',
        'anonymize_confirm' => 'Name und E-Mail werden überschrieben, alle Rollen, Mandanten- und Objektzuordnungen sowie API-Zugänge entzogen. Referenzierte Datensätze (Tickets, Aufgaben, Kostenbuchungen …) bleiben erhalten. Dies kann nicht rückgängig gemacht werden.',
        'anonymized' => 'Benutzer wurde anonymisiert.',
    ],
    'export' => [
        'title' => 'DSGVO-Auskunft',
        'master_data' => 'Stammdaten',
        'organizations' => 'Mandanten',
        'created_at' => 'Angelegt am',
        'email_verified_at' => 'E-Mail verifiziert am',
        'empty' => 'Keine weiteren personenbezogenen Daten in den geprüften Bereichen gefunden.',
        'footer' => 'Technischer Auszug — erhebt keinen Anspruch auf rechtliche Vollständigkeit.',
    ],
];
