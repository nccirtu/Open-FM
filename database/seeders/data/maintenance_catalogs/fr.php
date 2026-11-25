<?php

use App\Enums\MaintenanceIntervalUnit;
use App\Enums\MaintenanceType;

return [
    ['asset_type' => 'Aufzug', 'name' => 'Contrôle technique ascenseur', 'type' => MaintenanceType::Inspection, 'legal_basis' => 'Code de la construction R134-6', 'interval' => [5, MaintenanceIntervalUnit::Years], 'lead_time_days' => 60],
    ['asset_type' => 'Aufzug', 'name' => 'Entretien ascenseur', 'type' => MaintenanceType::Maintenance, 'legal_basis' => 'Arrêté du 18/11/2004', 'interval' => [6, MaintenanceIntervalUnit::Weeks], 'lead_time_days' => 14],
    ['asset_type' => 'Feuerlöscher', 'name' => 'Vérification extincteurs', 'type' => MaintenanceType::Inspection, 'legal_basis' => 'NF S61-919 / Code du travail R4227-39', 'interval' => [1, MaintenanceIntervalUnit::Years], 'lead_time_days' => 30],
    ['asset_type' => 'BMA', 'name' => 'Vérification système de sécurité incendie', 'type' => MaintenanceType::Inspection, 'legal_basis' => 'Règlement ERP art. MS 73', 'interval' => [1, MaintenanceIntervalUnit::Years], 'lead_time_days' => 30],
    ['asset_type' => 'Rauchabzugsanlage', 'name' => 'Vérification désenfumage', 'type' => MaintenanceType::Inspection, 'legal_basis' => 'Règlement ERP art. DF 10', 'interval' => [1, MaintenanceIntervalUnit::Years], 'lead_time_days' => 30],
    ['asset_type' => 'Notbeleuchtung', 'name' => 'Vérification éclairage de sécurité', 'type' => MaintenanceType::Inspection, 'legal_basis' => 'Code du travail R4226-14', 'interval' => [1, MaintenanceIntervalUnit::Years], 'lead_time_days' => 30],
    ['asset_type' => 'Automatiktür', 'name' => 'Entretien portes automatiques', 'type' => MaintenanceType::Maintenance, 'legal_basis' => 'Code du travail R4224-12', 'interval' => [6, MaintenanceIntervalUnit::Months], 'lead_time_days' => 14],
    ['asset_type' => 'Klimaanlage', 'name' => 'Inspection climatisation', 'type' => MaintenanceType::Inspection, 'legal_basis' => 'Décret 2020-912', 'interval' => [2, MaintenanceIntervalUnit::Years], 'lead_time_days' => 30],
    ['asset_type' => 'Lüftung', 'name' => 'Entretien ventilation', 'type' => MaintenanceType::Maintenance, 'legal_basis' => 'Code du travail R4222-20', 'interval' => [1, MaintenanceIntervalUnit::Years], 'lead_time_days' => 30],
    ['asset_type' => 'Heizung', 'name' => 'Entretien chaudière', 'type' => MaintenanceType::Maintenance, 'legal_basis' => 'Décret 2009-649', 'interval' => [1, MaintenanceIntervalUnit::Years], 'lead_time_days' => 30],
    ['asset_type' => 'Sprinkleranlage', 'name' => 'Vérification sprinklers', 'type' => MaintenanceType::Inspection, 'legal_basis' => 'Référentiel APSAD R1', 'interval' => [6, MaintenanceIntervalUnit::Months], 'lead_time_days' => 14],
];
