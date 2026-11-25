<?php

return [
    'actions' => [
        'export' => 'Extrait RGPD',
        'anonymize' => 'Anonymiser',
        'anonymize_confirm' => 'Le nom et l\'e-mail seront écrasés ; tous les rôles, affectations aux mandants et aux sites ainsi que les accès API seront retirés. Les enregistrements référencés (incidents, tâches, écritures de coûts …) sont conservés. Cette action est irréversible.',
        'anonymized' => 'L\'utilisateur a été anonymisé.',
    ],
    'export' => [
        'title' => 'Extrait RGPD',
        'master_data' => 'Données de base',
        'organizations' => 'Mandants',
        'created_at' => 'Créé le',
        'email_verified_at' => 'E-mail vérifié le',
        'empty' => 'Aucune autre donnée personnelle trouvée dans les domaines examinés.',
        'footer' => 'Extrait technique — sans prétention d\'exhaustivité juridique.',
    ],
];
