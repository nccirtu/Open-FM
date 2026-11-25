<?php

return [

    'model' => 'Incident',
    'models' => 'Incidents',
    'navigation' => 'Incidents',

    'fields' => [
        'ticket_number' => 'N° d\'incident',
        'property' => 'Site',
        'asset' => 'Équipement concerné',
        'type' => 'Type',
        'category' => 'Catégorie',
        'priority' => 'Priorité',
        'status' => 'Statut',
        'title' => 'Titre',
        'description' => 'Description',
        'photos' => 'Photos',
        'reporter' => 'Signalé par',
        'assignee' => 'Assigné à',
        'company' => 'Prestataire',
        'due_at' => 'Échéance le',
        'resolved_at' => 'Résolu le',
        'resolution_notes' => 'Note de résolution',
        'created_at' => 'Signalé le',
        'suspension_reason' => 'Motif d\'attente',
        'estimate_status' => 'Devis',
        'estimate_amount' => 'Montant du devis',
        'sla' => 'Échéance SLA',
    ],

    'sections' => [
        'report' => 'Signalement',
        'processing' => 'Traitement',
    ],

    'type' => [
        'fault' => 'Panne',
        'defect' => 'Défaut',
        'request' => 'Demande',
    ],

    'category' => [
        'electrical' => 'Électricité',
        'plumbing' => 'Plomberie',
        'hvac' => 'Chauffage / ventilation / climatisation',
        'refrigeration' => 'Froid',
        'building' => 'Bâtiment / gros œuvre',
        'safety' => 'Sécurité / protection incendie',
        'cleaning' => 'Nettoyage',
        'other' => 'Autre',
    ],

    'priority' => [
        'p1' => 'P1 — Urgence',
        'p2' => 'P2 — Prioritaire',
        'p3' => 'P3 — Standard',
    ],

    'status' => [
        'reported' => 'Signalé',
        'accepted' => 'Accepté',
        'in_progress' => 'En traitement',
        'waiting' => 'En attente',
        'resolved' => 'Résolu',
        'closed' => 'Clôturé',
    ],

    'suspension_reason' => [
        'awaiting_parts' => 'En attente de pièces détachées',
        'awaiting_partner' => 'En attente du prestataire',
        'other' => 'Autre',
    ],

    'estimate_status' => [
        'requested' => 'Demandé',
        'received' => 'Reçu',
        'approved' => 'Validé',
        'rejected' => 'Refusé',
    ],

    'sla' => [
        'model' => 'Règle SLA',
        'models' => 'Règles SLA',
        'navigation' => 'Règles SLA',
        'response_hours' => 'Délai de réaction (heures)',
        'response_hours_help' => 'Délai jusqu\'à la première réponse d\'un intervenant.',
        'resolution_hours' => 'Délai de résolution (heures)',
        'resolution_hours_help' => 'Délai jusqu\'à la résolution ; le statut « En attente » suspend le décompte.',
        'priority_unique' => 'Une règle SLA existe déjà pour cette priorité.',
        'escalation' => [
            'title' => 'SLA non respecté',
            'body' => 'L\'incident « :title » a dépassé son échéance SLA (délai de résolution : :due).',
        ],
    ],

    'filters' => [
        'open' => 'Uniquement ouverts',
    ],

    'new' => 'NOUVEAU',

    'reporter_stores' => 'Sites (affectation du déclarant)',
    'reporter_stores_help' => 'Les déclarants ne voient que ces sites dans le portail de services ; sans affectation, tous les sites de leur mandant.',

    'service_partner_help' => 'Pour les accès prestataires : les utilisateurs avec le rôle « Prestataire » ne voient que les incidents et ordres de maintenance de cette entreprise.',

    'replies' => [
        'title' => 'Historique & réponses',
        'message' => 'Message',
        'attachments' => 'Pièces jointes',
        'internal_note' => 'Note interne',
        'internal_note_help' => 'Les notes internes ne sont pas visibles par les déclarants dans le portail de services.',
        'author' => 'De',
        'created_at' => 'Date',
        'create' => 'Ajouter une réponse',
    ],

    'activities' => [
        'title' => 'Activités',
    ],

    'timeline' => [
        'automation' => 'L\'automatisation « :rule » a été exécutée.',
    ],

    'actions' => [
        'label' => 'Actions',
    ],

    'process' => [
        'action' => 'Traiter l\'incident',
        'heading' => 'Traiter l\'incident',
        'submit' => 'Enregistrer',
    ],

    'stats' => [
        'open' => 'Incidents ouverts',
        'p1_open' => 'Urgences ouvertes (P1)',
        'overdue' => 'En retard',
        'resolved_30d' => 'Résolus (30 jours)',
    ],

    'automations' => [
        'model' => 'Règle d\'automatisation',
        'models' => 'Règles d\'automatisation',
        'navigation' => 'Automatisations',
        'name' => 'Nom',
        'is_active' => 'Active',
        'event' => 'Événement',
        'events' => [
            'created' => 'Ticket signalé',
            'status_changed' => 'Statut modifié',
            'assigned' => 'Ticket assigné',
            'reply_added' => 'Réponse ajoutée',
        ],
        'conditions' => 'Conditions (toutes doivent être remplies)',
        'condition_field' => 'Champ',
        'condition_value' => 'Valeur',
        'condition_fields' => [
            'category' => 'Catégorie',
            'priority' => 'Priorité',
            'type' => 'Type',
            'status' => 'Statut',
        ],
        'actions' => 'Actions',
        'action_type' => 'Action',
        'action_value' => 'Valeur',
        'action_note' => 'Texte de la note',
        'action_types' => [
            'assign_to' => 'Assigner le ticket à',
            'set_status' => 'Définir le statut',
            'set_priority' => 'Définir la priorité',
            'add_internal_note' => 'Ajouter une note interne',
        ],
    ],

    'portal' => [
        'navigation' => 'Mes signalements',
        'report' => 'Signaler un incident',
        'report_for_asset' => 'Signaler un incident',
        'confirm' => [
            'action' => 'Confirmer la résolution',
            'heading' => 'Confirmer la résolution',
            'submit' => 'Confirmer & clôturer',
            'rating' => 'Satisfaction',
            'rating_help' => 'Êtes-vous satisfait de la résolution ? (optionnel)',
        ],
        'reopen' => [
            'action' => 'Rouvrir',
            'heading' => 'Rouvrir le signalement',
            'submit' => 'Rouvrir',
            'reason' => 'Qu\'est-ce qui n\'est pas encore en ordre ?',
            'reply_prefix' => 'Signalement rouvert :',
        ],
    ],

    'notifications' => [
        'assigned' => [
            'title' => 'Incident assigné',
            'body' => 'L\'incident « :title » (:property) vous a été assigné.',
        ],
        'reply' => [
            'title' => 'Nouvelle réponse',
            'body' => ':author a répondu à l\'incident « :title ».',
        ],
        'status' => [
            'title' => 'Statut de votre signalement modifié',
            'body' => 'Votre signalement « :title » a maintenant le statut « :status ».',
        ],
    ],

];
