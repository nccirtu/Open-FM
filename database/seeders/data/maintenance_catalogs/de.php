<?php

use App\Enums\MaintenanceIntervalUnit;
use App\Enums\MaintenanceType;

return [
    ['asset_type' => 'Aufzug', 'name' => 'Hauptprüfung durch ZÜS', 'type' => MaintenanceType::Inspection, 'legal_basis' => 'BetrSichV §16', 'interval' => [1, MaintenanceIntervalUnit::Years], 'lead_time_days' => 30],
    ['asset_type' => 'Aufzug', 'name' => 'Zwischenprüfung', 'type' => MaintenanceType::Inspection, 'legal_basis' => 'BetrSichV §16', 'interval' => [1, MaintenanceIntervalUnit::Years], 'lead_time_days' => 30, 'notes' => 'Zeitlich zwischen zwei Hauptprüfungen einplanen.'],
    ['asset_type' => 'Feuerlöscher', 'name' => 'Sachkundigenprüfung Feuerlöscher', 'type' => MaintenanceType::Inspection, 'legal_basis' => 'ASR A2.2 / DIN 14406-4', 'interval' => [2, MaintenanceIntervalUnit::Years], 'lead_time_days' => 30],
    ['asset_type' => 'BMA', 'name' => 'Wartung Brandmeldeanlage', 'type' => MaintenanceType::Maintenance, 'legal_basis' => 'DIN 14675', 'interval' => [1, MaintenanceIntervalUnit::Years], 'lead_time_days' => 30],
    ['asset_type' => 'Rauchabzugsanlage', 'name' => 'Wartung RWA', 'type' => MaintenanceType::Maintenance, 'legal_basis' => 'DIN 18232-2', 'interval' => [1, MaintenanceIntervalUnit::Years], 'lead_time_days' => 30],
    ['asset_type' => 'Notbeleuchtung', 'name' => 'Prüfung Sicherheitsbeleuchtung', 'type' => MaintenanceType::Inspection, 'legal_basis' => 'DIN VDE 0108-100', 'interval' => [1, MaintenanceIntervalUnit::Years], 'lead_time_days' => 30],
    ['asset_type' => 'Automatiktür', 'name' => 'Sachkundigenprüfung Automatiktür', 'type' => MaintenanceType::Inspection, 'legal_basis' => 'ASR A1.7 / DIN 18650', 'interval' => [1, MaintenanceIntervalUnit::Years], 'lead_time_days' => 30],
    ['asset_type' => 'Klimaanlage', 'name' => 'Hygieneinspektion Klimaanlage', 'type' => MaintenanceType::Inspection, 'legal_basis' => 'VDI 6022', 'interval' => [2, MaintenanceIntervalUnit::Years], 'lead_time_days' => 30],
    ['asset_type' => 'Lüftung', 'name' => 'Wartung Lüftungsanlage', 'type' => MaintenanceType::Maintenance, 'legal_basis' => 'VDI 6022', 'interval' => [1, MaintenanceIntervalUnit::Years], 'lead_time_days' => 30],
    ['asset_type' => 'Heizung', 'name' => 'Wartung Heizungsanlage', 'type' => MaintenanceType::Maintenance, 'legal_basis' => 'GEG / Herstellervorgabe', 'interval' => [1, MaintenanceIntervalUnit::Years], 'lead_time_days' => 30],
    ['asset_type' => 'Sprinkleranlage', 'name' => 'Prüfung Sprinkleranlage', 'type' => MaintenanceType::Inspection, 'legal_basis' => 'VdS CEA 4001', 'interval' => [1, MaintenanceIntervalUnit::Years], 'lead_time_days' => 30],
    ['asset_type' => 'Wandhydrant', 'name' => 'Prüfung Wandhydrant', 'type' => MaintenanceType::Inspection, 'legal_basis' => 'DIN 14462', 'interval' => [1, MaintenanceIntervalUnit::Years], 'lead_time_days' => 30],
    ['asset_type' => 'Hebeanlage', 'name' => 'Wartung Hebeanlage', 'type' => MaintenanceType::Maintenance, 'legal_basis' => 'DIN EN 12056-4', 'interval' => [6, MaintenanceIntervalUnit::Months], 'lead_time_days' => 14],
    ['asset_type' => 'CO2 Anlage', 'name' => 'Prüfung CO2-Löschanlage', 'type' => MaintenanceType::Inspection, 'legal_basis' => 'BetrSichV / DGUV 205-026', 'interval' => [1, MaintenanceIntervalUnit::Years], 'lead_time_days' => 30],
];
