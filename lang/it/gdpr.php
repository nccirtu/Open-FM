<?php

return [
    'actions' => [
        'export' => 'Estratto dati GDPR',
        'anonymize' => 'Anonimizza',
        'anonymize_confirm' => 'Nome ed e-mail verranno sovrascritti; tutti i ruoli, le assegnazioni a tenant e immobili nonché gli accessi API verranno revocati. I record collegati (guasti, attività, registrazioni costi …) restano invariati. L\'operazione non può essere annullata.',
        'anonymized' => 'L\'utente è stato anonimizzato.',
    ],
    'export' => [
        'title' => 'Estratto dati GDPR',
        'master_data' => 'Dati anagrafici',
        'organizations' => 'Tenant',
        'created_at' => 'Creato il',
        'email_verified_at' => 'E-mail verificata il',
        'empty' => 'Nessun altro dato personale trovato nelle aree esaminate.',
        'footer' => 'Estratto tecnico — non pretende di essere giuridicamente esaustivo.',
    ],
];
