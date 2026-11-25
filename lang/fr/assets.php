<?php

return [

    'child_assets' => 'Sous-équipements',
    'overview' => 'Vue d\'ensemble',
    'sections' => [
        'location' => 'Emplacement dans le bâtiment',
        'lifecycle' => 'Cycle de vie',
    ],

    'tabs' => [
        'general' => 'Général',
        'maintenance' => 'Maintenance & cycle de vie',
    ],

    'actions' => [
        'new' => 'Nouveau',
        'open' => 'Ouvrir',
        'create_asset_type_heading' => 'Créer un nouveau type d\'équipement',
        'create_company_heading' => 'Créer une nouvelle entreprise',
        'create_inspection_company_heading' => 'Créer un nouvel organisme de contrôle',
    ],

    'fields' => [
        'id' => 'ID',
        'name' => 'Nom',
        'image' => 'Image',
        'asset_number' => 'N° d\'équipement',
        'type' => 'Type',
        'manufacturer' => 'Fabricant',
        'serial_number' => 'Numéro de série',
        'serial_number_short' => 'N° de série',
        'maintenance_partner' => 'Prestataire de maintenance',
        'inspection_company' => 'Organisme de contrôle',
        'last_maintenance' => 'Dernière maintenance',
        'next_maintenance' => 'Prochaine maintenance',
        'kategorie' => 'Catégorie',
        'gruppe' => 'Groupe',
        'location' => 'Emplacement',
        'parent_asset' => 'Équipement parent',
        'condition' => 'État',
        'service_status' => 'Statut d\'exploitation',
        'warranty_until' => 'Garantie jusqu\'au',
        'installed_at' => 'Installé le',
        'decommissioned_at' => 'Mis hors service le',
        'purchase_price' => 'Prix d\'achat',
        'category' => 'Corps de métier',
    ],

    'condition' => [
        'good' => 'Bon',
        'fair' => 'Moyen',
        'poor' => 'Mauvais',
    ],

    'service_status' => [
        'in_operation' => 'En service',
        'out_of_operation' => 'Hors service',
        'in_storage' => 'En stock',
        'disposed' => 'Mis au rebut',
    ],

    'filters' => [
        'all' => 'Tous',
        'include_sub_locations' => 'y compris les sous-emplacements',
        'classification' => 'Classification',
        'maintenance_overdue' => 'En retard',
        'due_within' => 'À échéance dans :days jours',
        'maintenance_none' => 'Aucune maintenance prévue',
        'warranty_expired' => 'Expirée',
        'warranty_expiring' => 'Expire dans 90 jours',
        'warranty_valid' => 'Valide',
        'not_specified' => 'Non renseigné',
        'installed_from' => 'Installé à partir du',
        'installed_until' => 'Installé jusqu’au',
        'hierarchy' => 'Hiérarchie des équipements',
        'top_level_only' => 'Équipements principaux uniquement',
        'sub_assets_only' => 'Sous-équipements uniquement',
        'decommissioned' => 'Mis hors service',
        'active' => 'En service',
        'bim' => 'Maquette BIM',
        'bim_linked' => 'Lié à un élément BIM',
        'bim_not_linked' => 'Sans lien BIM',
    ],

    'import' => [
        'help' => [
            'asset_number' => 'Sert uniquement à mettre à jour des équipements existants (option « Mettre à jour les équipements existants »). Les nouveaux équipements sont numérotés automatiquement.',
            'property' => 'Numéro du site.',
            'location' => 'Nom ou chemin de l’emplacement dans le site, p. ex. « Bâtiment A › RDC › Couloir » (« / » ou « > » fonctionnent aussi).',
            'parent_asset' => 'N° ou nom de l’équipement parent dans le même site.',
        ],
        'options' => [
            'update_existing' => 'Mettre à jour les équipements existants via le n° (les cellules vides conservent les valeurs)',
            'create_missing' => 'Créer automatiquement les types d’équipement et entreprises inconnus',
        ],
        'template' => [
            'action' => 'Modèle d’import',
            'heading' => 'Télécharger le modèle d’import',
            'description' => 'Fichier CSV avec toutes les colonnes standard. Sélectionnez des types d’équipement pour obtenir aussi leurs champs spécifiques (p. ex. charge d’un extincteur) en colonnes. Les champs supplémentaires sont reconnus automatiquement par leur en-tête lors de l’import.',
            'download' => 'Télécharger',
            'asset_types' => 'Types d’équipement avec champs supplémentaires',
        ],
        'completed_title' => 'Import des équipements terminé',
        'completed_body' => '{0} Aucun équipement n’a été importé.|{1} :count équipement a été importé.|[2,*] :count équipements ont été importés.',
        'failed_rows' => '{1} :count ligne a échoué.|[2,*] :count lignes ont échoué.',
        'errors' => [
            'no_organization' => 'Aucun mandant défini pour l’import.',
            'property_not_found' => 'Site « :value » introuvable.',
            'location_without_property' => 'L’emplacement « :value » ne peut pas être attribué sans site.',
            'location_not_found' => 'Emplacement « :value » introuvable dans le site.',
            'location_ambiguous' => 'L’emplacement « :value » est ambigu – veuillez indiquer le chemin (p. ex. « Bâtiment A › RDC › Couloir »).',
            'asset_type_not_found' => 'Type d’équipement « :value » introuvable.',
            'parent_not_found' => 'Équipement parent « :value » introuvable.',
            'company_not_found' => 'Entreprise « :value » introuvable.',
            'invalid_number' => '« :value » n’est pas un nombre valide.',
            'invalid_boolean' => '« :value » n’est pas une valeur oui/non valide.',
            'invalid_date' => '« :value » n’est pas une date valide.',
            'invalid_option' => '« :value » n’est pas une option valide.',
        ],
    ],

];
