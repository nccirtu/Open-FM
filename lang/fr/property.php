<?php

return [

    'model' => 'Site',
    'models' => 'Sites',

    'qr' => [
        'print_structure' => 'Étiquettes QR structure',
        'print_assets' => 'Étiquettes QR équipements',
    ],

    'actions' => [
        'create_modal_heading' => 'Créer un nouveau site',
    ],

    'validation' => [
        'unique_number' => 'Un site avec ce numéro de site existe déjà.',
    ],

    'sections' => [
        'master' => 'Données de base',
        'overview' => 'Vue d\'ensemble',
        'areas' => 'Surfaces & données du bâtiment',
        'location_energy' => 'Localisation & énergie',
    ],

    'fields' => [
        'usage_type' => 'Type d’usage',
        'usage_type_help' => 'Détermine les indicateurs et les obligations dans le module Énergie.',
        'construction_year' => 'Année de construction',
        'construction_year_help' => 'Sert aux alertes sur les substances dangereuses avant démolition (p. ex. amiante avant 1993).',
        'manager' => 'Gestionnaire',
        'open_tickets' => 'Incidents ouverts',
        'property_number' => 'Numéro de site',
        'street' => 'Rue et numéro',
        'zip_code' => 'Code postal',
        'city' => 'Ville',
        'state' => 'Région',
        'country' => 'Pays',
        'gross_area' => 'Surface brute (m²)',
        'net_area' => 'Surface nette (m²)',
        'cleanable_area' => 'Surface à nettoyer (m²)',
        'heated_volume' => 'Volume chauffé (m³)',
        'latitude' => 'Latitude',
        'longitude' => 'Longitude',
        'energy_rating' => 'Classe de performance énergétique',
        'energy_cert_valid_until' => 'Certificat énergétique valide jusqu\'au',
    ],

    'structure' => [
        'model' => 'Élément de structure',
        'models' => 'Structure',
        'location' => 'Emplacement',
        'name' => 'Libellé',
        'type' => 'Type',
        'parent' => 'Élément parent',
        'level' => 'Niveau',
        'level_help' => '0 = rez-de-chaussée, négatif = sous-sol',
        'room_use' => 'Usage',
        'area' => 'Surface (m²)',
        'height' => 'Hauteur (m)',
        'heated' => 'Chauffé',
        'sort' => 'Ordre',
        'assets_count' => 'Équipements',
        'total_area' => 'Surface totale',
        'rooms_count' => 'Locaux',
        'nodes_count' => 'Éléments de structure',
        'types' => [
            'building' => 'Bâtiment',
            'floor' => 'Étage',
            'unit' => 'Unité d\'usage',
            'room' => 'Local',
            'zone' => 'Zone/secteur',
            'outdoor' => 'Espace extérieur',
        ],
    ],

    'bim' => [
        'title' => 'BIM — site :property',
        'upload' => 'Téléverser un modèle IFC',
        'file' => 'Fichier IFC',
        'ifc_guid' => 'GUID IFC',
        'file_help' => 'IFC 2x3 ou IFC4. Les fichiers volumineux peuvent prendre un moment.',
        'file_extension' => 'Seuls les fichiers IFC (.ifc) sont autorisés.',
        'upload_rejected' => 'Le téléversement a été refusé car le chemin du fichier est invalide.',
        'uploaded' => 'Modèle BIM téléversé',
        'uploaded_at' => 'Téléversé le',
        'empty' => 'Aucun modèle BIM n\'a encore été téléversé pour ce site. Utilisez « Téléverser un modèle IFC » en haut à droite.',
        'open_full' => 'Ouvrir la vue complète',
        'show_in_model' => 'Afficher dans le modèle BIM',
        'inspector' => [
            'title' => 'Composant',
            'linked_asset' => 'Équipement lié',
            'linked_node' => 'Élément de structure lié',
            'no_link' => 'Aucun élément lié dans le CAFM — vous pouvez créer ce composant directement comme équipement.',
            'open_asset' => 'Ouvrir l\'équipement',
            'open_structure' => 'Ouvrir la structure',
            'create_asset' => 'Créer comme équipement',
            'asset_created' => 'Équipement créé à partir du composant BIM',
        ],
    ],

    'map' => [
        'heading' => 'Sites sur la carte',
        'open_tickets' => ':count incidents ouverts',
        'overdue_orders' => ':count contrôles/maintenances en retard',
    ],

    'room_use' => [
        'sales_floor' => 'Surface de vente',
        'storage' => 'Entrepôt',
        'office' => 'Bureau',
        'technical' => 'Local technique',
        'sanitary' => 'Sanitaires',
        'staff_room' => 'Salle du personnel',
        'entrance' => 'Entrée/sas',
        'corridor' => 'Couloir',
        'outdoor' => 'Espace extérieur',
        'waste_collection' => 'Point de collecte des déchets',
        'other' => 'Autre',
    ],

    'tabs' => [
        'mine' => 'Mes sites',
        'all' => 'Tous les sites',
    ],

    'usage_type' => [
        'commercial' => 'Commercial',
        'residential' => 'Résidentiel',
        'mixed' => 'Mixte',
    ],

];
