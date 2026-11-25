<?php

return [

    'tab' => 'Énergie',

    'meter' => [
        'model' => 'Compteur',
        'models' => 'Compteurs',
        'measured_assets' => 'Consommateurs',
        'supplied_nodes' => 'Locaux desservis',
    ],

    'reading' => [
        'model' => 'Relevé',
        'models' => 'Relevés',
    ],

    'readings' => [
        'none' => 'Aucun relevé pour l’instant',
    ],

    'goal' => [
        'model' => 'Objectif',
        'models' => 'Objectifs',
        'all_properties' => 'Tous les sites',
        'asset' => 'Équipement',
        'max' => 'max.',
        'vs_year' => 'vs :year',
        'actual' => 'à ce jour',
        'forecast' => 'Projection',
        'target' => 'Objectif',
        'empty' => 'Aucun objectif n’a encore été défini pour cette année.',
    ],

    'pages' => [
        'record_readings' => 'Relever',
    ],

    'sections' => [
        'master' => 'Données de base',
        'formula' => 'Calcul',
    ],

    'fields' => [
        'name' => 'Désignation',
        'number' => 'N° de compteur',
        'medium' => 'Type d’énergie',
        'kind' => 'Type de compteur',
        'parent' => 'Rattaché à',
        'unit' => 'Unité du totalisateur',
        'factor' => 'Facteur de conversion',
        'location' => 'Emplacement',
        'device_asset' => 'Appareil de comptage (équipement)',
        'market_location_id' => 'Point de livraison / de comptage (PDL/PCE)',
        'calibration_valid_until' => 'Étalonnage valable jusqu’au',
        'is_active' => 'Actif',
        'notes' => 'Notes',
        'sign' => 'Signe',
        'share' => 'Part',
        'last_reading' => 'Dernier index',
        'read_at' => 'Date du relevé',
        'value' => 'Index',
        'photo' => 'Photo du compteur',
        'note' => 'Remarque',
        'source' => 'Source',
        'status' => 'Contrôle',
        'created_by' => 'Saisi par',
        'consumption_year' => 'Consommation :year',
        'goal_scope' => 'S’applique à',
        'goal_type' => 'Type d’objectif',
        'goal_period' => 'Période',
        'year' => 'Année',
        'target_value' => 'Valeur cible',
        'baseline_year' => 'Année de référence',
        'progress' => 'Progression',
    ],

    'help' => [
        'parent' => 'Compteur parent dans l’arborescence des compteurs, p. ex. le compteur principal. Vide pour les compteurs principaux.',
        'factor' => 'Convertit l’index en kWh, p. ex. pour le gaz : pouvoir calorifique × coefficient de conversion (environ 10,5). 1 pour les compteurs en kWh.',
        'device_asset' => 'Facultatif : l’appareil en tant qu’équipement, p. ex. pour la plaque signalétique, l’étiquette QR et la maintenance.',
        'formula' => 'Un compteur calculé n’a pas d’appareil. Sa consommation est déduite d’autres compteurs, p. ex. compteur principal moins tous les sous-compteurs.',
        'share' => 'Si le compteur mesure plusieurs équipements ou zones, sa consommation est répartie selon ces parts.',
        'goal_scope' => 'Laisser vide pour un objectif portant sur tous les sites.',
        'market_location_id' => 'Identifiant du raccordement attribué par le gestionnaire de réseau (figure sur chaque facture d\'électricité et de gaz, p. ex. PDL/PCE). Uniquement pour les compteurs principaux, p. ex. pour rapprocher factures et données du gestionnaire de réseau.',
    ],

    'medium' => [
        'electricity' => 'Électricité',
        'gas' => 'Gaz',
        'heat' => 'Chaleur',
        'cold' => 'Froid',
        'water' => 'Eau',
        'oil' => 'Fioul',
        'pv_generation' => 'Production PV',
    ],

    'kind' => [
        'main' => 'Compteur principal',
        'sub' => 'Sous-compteur',
        'virtual' => 'Compteur calculé',
    ],

    'source' => [
        'manual' => 'Mobile / Web',
        'import' => 'Import',
        'api' => 'Interface',
    ],

    'status' => [
        'plausible' => 'plausible',
        'suspicious' => 'suspect',
    ],

    'goal_type' => [
        'max_consumption' => 'Consommation maximale',
        'max_intensity' => 'Ratio par m²',
        'reduction' => 'Réduction par rapport à l’année de référence',
    ],

    'goal_period' => [
        'year' => 'Année',
        'month' => 'par mois',
    ],

    'goal_status' => [
        'good' => 'en bonne voie',
        'warn' => 'menacé',
        'crit' => 'dépassé',
        'unknown' => 'pas de référence',
    ],

    'plausibility' => [
        'decreased' => 'L’index est inférieur au précédent. Faute de frappe ou changement de compteur ?',
        'unusually_high' => 'Anormalement élevé : :actual :unit par jour, habituellement environ :expected :unit. Veuillez vérifier et prendre une photo.',
        'short' => [
            'decreased' => 'inférieur au précédent',
            'unusually_high' => 'anormalement élevé',
        ],
    ],

    'actions' => [
        'record_reading' => 'Saisir un relevé',
        'save_reading' => 'Enregistrer l’index',
        'add_formula_term' => 'Ajouter un compteur',
        'attach_asset' => 'Associer un équipement',
        'attach_node' => 'Associer un local',
        'edit_share' => 'Modifier la part',
        'import_readings' => 'Importer des relevés',
        'open_in_energy' => 'Ouvrir dans le module Énergie',
        'all_goals' => 'Tous les objectifs',
    ],

    'notifications' => [
        'saved' => 'Index enregistré',
        'saved_suspicious' => 'Index enregistré, mais suspect',
    ],

    'errors' => [
        'virtual_meter' => 'Impossible de saisir des index pour un compteur calculé.',
    ],

    'filters' => [
        'calibration_due' => 'Étalonnage à échéance (180 jours)',
        'from' => 'Du',
        'until' => 'Au',
        'period' => 'Période',
        'all_usage_types' => 'Tous les types d\'usage',
        'all_properties' => 'Tous les sites',
        'periods' => [
            'this_month' => 'Ce mois-ci',
            'last_month' => 'Mois dernier',
            'last_30_days' => '30 derniers jours',
            'this_quarter' => 'Ce trimestre',
            'this_year' => 'Cette année',
            'last_year' => 'Année dernière',
            'last_12_months' => '12 derniers mois',
            'custom' => 'Période personnalisée',
        ],
    ],

    'stats' => [
        'energy_in_period' => 'Énergie sur la période',
        'water_in_period' => 'Eau sur la période',
        'goals_on_track' => 'Objectifs en bonne voie',
        'suspicious_readings' => 'Relevés suspects',
        'calibration_due' => 'Étalonnage à échéance',
        'next_180_days' => 'dans les 180 prochains jours',
        'vs_previous_year' => ':change % vs année précédente',
        'no_comparison' => 'pas de valeur de l’année précédente',
        'year_to_date' => 'Consommation :year à ce jour',
        'previous_year_to_date' => 'Même période :year',
    ],

    'charts' => [
        'consumption_trend' => 'Consommation par mois',
        'consumption_per_day' => 'Consommation par jour',
        'meter_consumption' => 'Consommation',
        'by_asset' => 'Principaux consommateurs',
        'consumption_in' => 'Consommation en :unit',
        'previous_year' => 'Année précédente',
        'last_30_days' => '30 derniers jours',
        'last_12_months' => '12 derniers mois',
    ],

    'flow' => [
        'title' => 'Flux d’énergie',
        'description' => 'Des compteurs principaux aux équipements, en passant par les sous-compteurs. Largeur = quantité.',
        'empty' => 'Aucune consommation pour ce site et ce type d’énergie sur la période.',
        'unassigned' => 'non affecté',
    ],

    'consumers' => [
        'title' => 'Consommateurs et flux d\'énergie',
        'description' => 'Où va la consommation de ce compteur : via les sous-compteurs jusqu\'aux équipements affectés. Largeur = quantité.',
        'empty' => 'Aucun consommateur n\'est encore affecté à ce compteur. Affectez les équipements qu\'il mesure dans l\'onglet « Consommateurs » ou placez des sous-compteurs en dessous.',
        'no_consumption' => 'Aucune consommation pour ce compteur sur l\'année sélectionnée.',
        'list' => 'Principaux consommateurs',
        'hint' => 'Si un compteur mesure plusieurs équipements, sa consommation est répartie selon les parts enregistrées. « :unassigned » correspond au reste qu\'aucun équipement ni sous-compteur n\'explique.',
    ],

    'comparison' => [
        'title' => 'Comparaison des biens',
        'energy' => 'Énergie sur la période',
        'intensity' => 'par m²',
        'change' => 'vs année précédente',
    ],

    'calibration' => [
        'title' => 'Étalonnage à échéance',
        'empty' => 'Aucun étalonnage à échéance dans les 180 prochains jours',
    ],

    'suspicious' => [
        'title' => 'Relevés suspects',
        'empty' => 'Aucun relevé suspect sur la période',
    ],

    'import' => [
        'help' => [
            'meter' => 'Numéro de compteur, à défaut la désignation unique du compteur.',
        ],
        'completed_title' => 'Import des relevés terminé',
        'completed_body' => '{0} Aucun index n’a été importé.|{1} :count index a été importé.|[2,*] :count index ont été importés.',
        'errors' => [
            'meter_not_found' => 'Compteur « :value » introuvable.',
            'meter_ambiguous' => 'Le compteur « :value » n’est pas unique – veuillez utiliser le numéro de compteur.',
        ],
    ],

];
