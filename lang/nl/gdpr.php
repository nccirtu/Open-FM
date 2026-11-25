<?php

return [
    'actions' => [
        'export' => 'AVG-inzage',
        'anonymize' => 'Anonimiseren',
        'anonymize_confirm' => 'Naam en e-mailadres worden overschreven; alle rollen, tenant- en objecttoewijzingen en API-toegangen worden ingetrokken. Gekoppelde records (storingen, taken, kostenboekingen …) blijven behouden. Dit kan niet ongedaan worden gemaakt.',
        'anonymized' => 'Gebruiker is geanonimiseerd.',
    ],
    'export' => [
        'title' => 'AVG-inzage',
        'master_data' => 'Stamgegevens',
        'organizations' => 'Tenants',
        'created_at' => 'Aangemaakt op',
        'email_verified_at' => 'E-mail geverifieerd op',
        'empty' => 'Geen verdere persoonsgegevens gevonden in de gecontroleerde onderdelen.',
        'footer' => 'Technisch uittreksel — pretendeert geen juridische volledigheid.',
    ],
];
