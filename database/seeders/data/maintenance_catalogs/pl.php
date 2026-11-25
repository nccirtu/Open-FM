<?php

use App\Enums\MaintenanceIntervalUnit;
use App\Enums\MaintenanceType;

return [
    ['asset_type' => 'Aufzug', 'name' => 'Badanie okresowe UDT', 'type' => MaintenanceType::Inspection, 'legal_basis' => 'Ustawa o dozorze technicznym', 'interval' => [1, MaintenanceIntervalUnit::Years], 'lead_time_days' => 30],
    ['asset_type' => 'Aufzug', 'name' => 'Konserwacja dźwigu', 'type' => MaintenanceType::Maintenance, 'legal_basis' => 'Rozporządzenie MPiT 30.10.2018', 'interval' => [1, MaintenanceIntervalUnit::Months], 'lead_time_days' => 7],
    ['asset_type' => 'Feuerlöscher', 'name' => 'Przegląd gaśnic', 'type' => MaintenanceType::Inspection, 'legal_basis' => 'Rozporządzenie MSWiA / PN-EN 3', 'interval' => [1, MaintenanceIntervalUnit::Years], 'lead_time_days' => 30],
    ['asset_type' => 'BMA', 'name' => 'Przegląd systemu sygnalizacji pożarowej', 'type' => MaintenanceType::Inspection, 'legal_basis' => 'Rozporządzenie MSWiA 7.06.2010', 'interval' => [1, MaintenanceIntervalUnit::Years], 'lead_time_days' => 30],
    ['asset_type' => 'Rauchabzugsanlage', 'name' => 'Przegląd systemu oddymiania', 'type' => MaintenanceType::Inspection, 'legal_basis' => 'Rozporządzenie MSWiA 7.06.2010', 'interval' => [1, MaintenanceIntervalUnit::Years], 'lead_time_days' => 30],
    ['asset_type' => 'Notbeleuchtung', 'name' => 'Przegląd oświetlenia awaryjnego', 'type' => MaintenanceType::Inspection, 'legal_basis' => 'PN-EN 50172', 'interval' => [1, MaintenanceIntervalUnit::Years], 'lead_time_days' => 30],
    ['asset_type' => 'Automatiktür', 'name' => 'Konserwacja drzwi automatycznych', 'type' => MaintenanceType::Maintenance, 'legal_basis' => 'Zalecenia producenta', 'interval' => [6, MaintenanceIntervalUnit::Months], 'lead_time_days' => 14],
    ['asset_type' => 'Klimaanlage', 'name' => 'Przegląd klimatyzacji', 'type' => MaintenanceType::Maintenance, 'legal_basis' => 'Prawo budowlane art. 62', 'interval' => [1, MaintenanceIntervalUnit::Years], 'lead_time_days' => 30],
    ['asset_type' => 'Lüftung', 'name' => 'Przegląd wentylacji', 'type' => MaintenanceType::Inspection, 'legal_basis' => 'Prawo budowlane art. 62', 'interval' => [1, MaintenanceIntervalUnit::Years], 'lead_time_days' => 30],
    ['asset_type' => 'Heizung', 'name' => 'Przegląd kotłowni', 'type' => MaintenanceType::Maintenance, 'legal_basis' => 'Prawo budowlane art. 62', 'interval' => [1, MaintenanceIntervalUnit::Years], 'lead_time_days' => 30],
    ['asset_type' => 'Hebeanlage', 'name' => 'Konserwacja przepompowni', 'type' => MaintenanceType::Maintenance, 'legal_basis' => 'PN-EN 12056-4', 'interval' => [6, MaintenanceIntervalUnit::Months], 'lead_time_days' => 14],
];
