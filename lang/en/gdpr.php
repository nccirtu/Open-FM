<?php

return [
    'actions' => [
        'export' => 'GDPR export',
        'anonymize' => 'Anonymize',
        'anonymize_confirm' => 'Name and email will be overwritten, and all roles, tenant/property assignments and API access revoked. Referenced records (tickets, tasks, cost entries, …) are kept. This cannot be undone.',
        'anonymized' => 'User has been anonymized.',
    ],
    'export' => [
        'title' => 'GDPR export',
        'master_data' => 'Master data',
        'organizations' => 'Tenants',
        'created_at' => 'Created at',
        'email_verified_at' => 'Email verified at',
        'empty' => 'No further personal data found in the checked areas.',
        'footer' => 'Technical export — makes no claim to legal completeness.',
    ],
];
