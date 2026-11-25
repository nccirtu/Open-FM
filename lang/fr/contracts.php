<?php

return [

    'model' => 'Contrat',
    'models' => 'Contrats',
    'navigation' => 'Contrats',

    'sections' => [
        'master_data' => 'Données du contrat',
        'term' => 'Durée & résiliation',
        'costs' => 'Coûts',
        'assignment' => 'Affectation & documents',
    ],

    'fields' => [
        'name' => 'Libellé',
        'contract_number' => 'Numéro de contrat',
        'partner_name' => 'Partenaire contractuel',
        'type' => 'Type de contrat',
        'status' => 'Statut',
        'start_date' => 'Début du contrat',
        'end_date' => 'Fin du contrat',
        'end_date_help' => 'Laisser vide pour un contrat à durée indéterminée.',
        'open_ended' => 'durée indéterminée',
        'notice_period_days' => 'Préavis de résiliation (jours)',
        'notice_period_days_help' => 'Délai avant la fin du contrat pendant lequel la résiliation doit intervenir.',
        'reminder_lead_days' => 'Délai de rappel (jours)',
        'reminder_lead_days_help' => 'Le rappel est envoyé ce nombre de jours avant la date limite de résiliation.',
        'notice_deadline' => 'Date limite de résiliation',
        'amount' => 'Montant',
        'billing_cycle' => 'Périodicité de facturation',
        'properties' => 'Sites',
        'assets' => 'Équipements',
        'notes' => 'Remarques',
    ],

    'type' => [
        'maintenance' => 'Contrat de maintenance',
        'service' => 'Contrat de prestations',
        'rental' => 'Contrat de location',
        'insurance' => 'Assurance',
        'utility' => 'Fourniture (électricité/eau/…)',
        'waste_disposal' => 'Élimination des déchets',
        'other' => 'Autre',
    ],

    'status' => [
        'active' => 'Actif',
        'terminated' => 'Résilié',
        'expired' => 'Expiré',
    ],

    'billing_cycle' => [
        'monthly' => 'Mensuelle',
        'quarterly' => 'Trimestrielle',
        'yearly' => 'Annuelle',
        'one_time' => 'Unique',
    ],

    'reminder' => [
        'title' => 'Préavis de résiliation en cours : :name',
        'body' => 'Contrat avec :partner — résiliation possible dans les délais jusqu\'au :deadline (fin du contrat : :end). À vérifier et décider maintenant.',
    ],

    'stats' => [
        'active' => 'Contrats actifs',
        'needs_decision' => 'Date limite de résiliation imminente',
        'yearly_costs' => 'Coûts annuels',
    ],

];
