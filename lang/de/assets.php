<?php

return [

    'child_assets' => 'Unter-Assets',
    'overview' => 'Übersicht',
    'sections' => [
        'location' => 'Standort im Gebäude',
        'lifecycle' => 'Lebenszyklus',
    ],

    'tabs' => [
        'general' => 'Allgemein',
        'maintenance' => 'Wartung & Lebenszyklus',
    ],

    'actions' => [
        'new' => 'Neu',
        'open' => 'Öffnen',
        'create_asset_type_heading' => 'Neuen Asset-Typ anlegen',
        'create_company_heading' => 'Neuen Company anlegen',
        'create_inspection_company_heading' => 'Neue Prüffirma anlegen',
    ],

    'fields' => [
        'id' => 'ID',
        'name' => 'Name',
        'image' => 'Bild',
        'asset_number' => 'Asset-Nr.',
        'type' => 'Typ',
        'manufacturer' => 'Hersteller',
        'serial_number' => 'Seriennummer',
        'serial_number_short' => 'Seriennr.',
        'maintenance_partner' => 'Wartungspartner',
        'inspection_company' => 'Prüffirma',
        'last_maintenance' => 'Letzte Wartung',
        'next_maintenance' => 'Nächste Wartung',
        'kategorie' => 'Kategorie',
        'gruppe' => 'Gruppe',
        'location' => 'Standort',
        'parent_asset' => 'Übergeordnete Anlage',
        'condition' => 'Zustand',
        'service_status' => 'Betriebsstatus',
        'warranty_until' => 'Garantie bis',
        'installed_at' => 'Installiert am',
        'decommissioned_at' => 'Stillgelegt am',
        'purchase_price' => 'Kaufpreis',
        'category' => 'Gewerk',
    ],

    'condition' => [
        'good' => 'Gut',
        'fair' => 'Mittel',
        'poor' => 'Schlecht',
    ],

    'service_status' => [
        'in_operation' => 'In Betrieb',
        'out_of_operation' => 'Außer Betrieb',
        'in_storage' => 'Eingelagert',
        'disposed' => 'Entsorgt',
    ],

    'filters' => [
        'all' => 'Alle',
        'include_sub_locations' => 'inkl. untergeordneter Standorte',
        'classification' => 'Klassifikation',
        'maintenance_overdue' => 'Überfällig',
        'due_within' => 'Fällig in :days Tagen',
        'maintenance_none' => 'Keine Wartung geplant',
        'warranty_expired' => 'Abgelaufen',
        'warranty_expiring' => 'Läuft in 90 Tagen ab',
        'warranty_valid' => 'Gültig',
        'not_specified' => 'Keine Angabe',
        'installed_from' => 'Installiert ab',
        'installed_until' => 'Installiert bis',
        'hierarchy' => 'Anlagenhierarchie',
        'top_level_only' => 'Nur Hauptanlagen',
        'sub_assets_only' => 'Nur Unteranlagen',
        'decommissioned' => 'Stillgelegt',
        'active' => 'In Nutzung',
        'bim' => 'BIM-Modell',
        'bim_linked' => 'Mit BIM-Bauteil verknüpft',
        'bim_not_linked' => 'Ohne BIM-Verknüpfung',
    ],

    'import' => [
        'help' => [
            'asset_number' => 'Nur zum Aktualisieren bestehender Assets (Option „Bestehende Assets aktualisieren“). Neue Assets erhalten automatisch eine Nummer.',
            'property' => 'Objektnummer des Objekts.',
            'location' => 'Name oder Pfad des Standorts im Objekt, z. B. „Gebäude A › EG › Flur“ (auch mit „/“ oder „>“ getrennt).',
            'parent_asset' => 'Asset-Nr. oder Name der übergeordneten Anlage im selben Objekt.',
        ],
        'options' => [
            'update_existing' => 'Bestehende Assets über die Asset-Nr. aktualisieren (leere Zellen lassen Werte unverändert)',
            'create_missing' => 'Unbekannte Asset-Typen und Firmen automatisch anlegen',
        ],
        'template' => [
            'action' => 'Importvorlage',
            'heading' => 'Importvorlage herunterladen',
            'description' => 'CSV-Datei mit allen Standardspalten. Wählen Sie Asset-Typen aus, um zusätzlich deren typabhängige Felder (z. B. Füllmenge beim Feuerlöscher) als Spalten zu erhalten. Zusatzfelder werden beim Import automatisch über den Spaltenkopf erkannt.',
            'download' => 'Herunterladen',
            'asset_types' => 'Asset-Typen mit Zusatzfeldern',
        ],
        'completed_title' => 'Asset-Import abgeschlossen',
        'completed_body' => '{0} Es wurde kein Asset importiert.|{1} :count Asset wurde importiert.|[2,*] :count Assets wurden importiert.',
        'failed_rows' => '{1} :count Zeile ist fehlgeschlagen.|[2,*] :count Zeilen sind fehlgeschlagen.',
        'errors' => [
            'no_organization' => 'Kein Mandant für den Import festgelegt.',
            'property_not_found' => 'Objekt „:value“ nicht gefunden.',
            'location_without_property' => 'Standort „:value“ kann ohne Objekt nicht zugeordnet werden.',
            'location_not_found' => 'Standort „:value“ im Objekt nicht gefunden.',
            'location_ambiguous' => 'Standort „:value“ ist nicht eindeutig – bitte den Pfad angeben (z. B. „Gebäude A › EG › Flur“).',
            'asset_type_not_found' => 'Asset-Typ „:value“ nicht gefunden.',
            'parent_not_found' => 'Übergeordnete Anlage „:value“ nicht gefunden.',
            'company_not_found' => 'Firma „:value“ nicht gefunden.',
            'invalid_number' => '„:value“ ist keine gültige Zahl.',
            'invalid_boolean' => '„:value“ ist kein gültiger Ja/Nein-Wert.',
            'invalid_date' => '„:value“ ist kein gültiges Datum.',
            'invalid_option' => '„:value“ ist keine gültige Auswahl.',
        ],
    ],

];
