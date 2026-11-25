<?php

return [

    'navigation_group' => 'Maintenance & contrôles',

    'plans' => [
        'model' => 'Plan de maintenance',
        'models' => 'Plans de maintenance',
        'navigation' => 'Plans de maintenance',
        'generate_order' => 'Générer un ordre',
        'order_created' => 'Ordre de maintenance généré',
        'order_exists' => 'Un ordre existe déjà pour cette échéance',
    ],

    'orders' => [
        'model' => 'Ordre de maintenance',
        'models' => 'Ordres de maintenance',
        'navigation' => 'Ordres de maintenance',
        'filters' => [
            'overdue' => 'Uniquement en retard',
        ],
        'complete' => [
            'action' => 'Clôturer',
            'heading' => 'Clôturer l\'ordre',
            'submit' => 'Clôturer',
            'tasks' => 'Points de contrôle effectués',
            'tasks_help' => 'Les points cochés sont enregistrés sur l\'ordre de manière infalsifiable.',
            'protocol' => 'Procès-verbal de contrôle/maintenance',
            'protocol_help' => 'Documents justificatifs (PDF, photos) — archivage infalsifiable sur l\'ordre.',
            'defect_found' => 'Défaut constaté',
            'defect_found_help' => 'Crée automatiquement un signalement de défaut sur l\'équipement concerné.',
            'defect_title' => 'Titre du défaut',
            'defect_description' => 'Description du défaut',
            'defect_source' => 'Constaté lors de : :plan (:date)',
            'done' => 'Ordre clôturé',
        ],
    ],

    'fields' => [
        'asset' => 'Équipement',
        'name' => 'Libellé',
        'name_placeholder' => 'p. ex. contrôle DGUV V3',
        'type' => 'Type',
        'legal_basis' => 'Base réglementaire',
        'legal_basis_placeholder' => 'p. ex. DGUV V3, BetrSichV, TrinkwV',
        'interval' => 'Intervalle',
        'interval_value' => 'Intervalle (nombre)',
        'interval_unit' => 'Intervalle (unité)',
        'next_due_at' => 'Prochaine échéance',
        'lead_time_days' => 'Délai d\'anticipation (jours)',
        'lead_time_days_help' => 'L\'ordre est généré automatiquement ce nombre de jours avant l\'échéance.',
        'inspection_company' => 'Organisme de contrôle',
        'tasks' => 'Points de contrôle (check-list)',
        'tasks_help' => 'Ces points apparaissent en check-list dans la fenêtre de clôture de chaque ordre.',
        'tasks_add' => 'Ajouter un point de contrôle',
        'is_active' => 'Actif',
        'notes' => 'Remarques',
        'due_at' => 'Échéance le',
        'status' => 'Statut',
        'completed_at' => 'Clôturé le',
        'completed_by' => 'Clôturé par',
    ],

    'type' => [
        'maintenance' => 'Maintenance',
        'inspection' => 'Contrôle réglementaire',
    ],

    'interval_unit' => [
        'days' => 'Jours',
        'weeks' => 'Semaines',
        'months' => 'Mois',
        'years' => 'Années',
    ],

    'order_status' => [
        'open' => 'Ouvert',
        'completed' => 'Clôturé',
        'skipped' => 'Ignoré',
    ],

    'catalogs' => [
        'import_label' => 'Importer un catalogue de maintenance',
        'import_help' => 'Importe les obligations de contrôle et maintenances propres à chaque pays comme modèles par type d\'équipement.',
        'import_submit' => 'Importer',
        'imported' => ':count modèles importés.',
        'import_none' => 'Aucun import',
        'countries' => [
            'de' => 'Allemagne',
            'fr' => 'France',
            'pl' => 'Pologne',
        ],
    ],

    'templates' => [
        'model' => 'Modèle de catalogue de maintenance',
        'models' => 'Catalogue de maintenance',
        'navigation' => 'Catalogue de maintenance',
        'asset_type' => 'Type d\'équipement',
        'country' => 'Pays',
        'plans_count' => 'Plans générés',
    ],

    'stats' => [
        'overdue' => 'Contrôles/maintenances en retard',
        'due_30d' => 'À échéance sous 30 jours',
        'open' => 'Ordres ouverts',
        'active_plans' => 'Plans actifs',
    ],

];
