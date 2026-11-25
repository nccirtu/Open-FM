<?php

return [

    'model' => 'Objekt',
    'models' => 'Objekte',

    'qr' => [
        'print_structure' => 'QR-Etiketten Struktur',
        'print_assets' => 'QR-Etiketten Assets',
    ],

    'actions' => [
        'create_modal_heading' => 'Neues Objekt anlegen',
    ],

    'validation' => [
        'unique_number' => 'Ein Objekt mit dieser Objektnummer existiert bereits.',
    ],

    'sections' => [
        'master' => 'Stammdaten',
        'overview' => 'Übersicht',
        'areas' => 'Flächen & Gebäudedaten',
        'location_energy' => 'Lage & Energie',
    ],

    'fields' => [
        'usage_type' => 'Nutzungsart',
        'usage_type_help' => 'Steuert Kennzahlen und Pflichten im Energie-Bereich.',
        'construction_year' => 'Baujahr',
        'construction_year_help' => 'Für Schadstoffhinweise bei Rückbau (z. B. Asbest vor 1993).',
        'manager' => 'Betreuer',
        'open_tickets' => 'Offene Störungen',
        'property_number' => 'Objektnummer',
        'street' => 'Straße und Hausnummer',
        'zip_code' => 'PLZ',
        'city' => 'Stadt',
        'state' => 'Bundesland',
        'country' => 'Land',
        'gross_area' => 'Bruttofläche (m²)',
        'net_area' => 'Nettofläche (m²)',
        'cleanable_area' => 'Reinigungsfläche (m²)',
        'heated_volume' => 'Beheiztes Volumen (m³)',
        'latitude' => 'Breitengrad',
        'longitude' => 'Längengrad',
        'energy_rating' => 'Energieeffizienzklasse',
        'energy_cert_valid_until' => 'Energieausweis gültig bis',
    ],

    'structure' => [
        'model' => 'Strukturelement',
        'models' => 'Struktur',
        'location' => 'Standort',
        'name' => 'Bezeichnung',
        'type' => 'Typ',
        'parent' => 'Übergeordnetes Element',
        'level' => 'Ebene',
        'level_help' => '0 = Erdgeschoss, negativ = Untergeschoss',
        'room_use' => 'Nutzungsart',
        'area' => 'Fläche (m²)',
        'height' => 'Höhe (m)',
        'heated' => 'Beheizt',
        'sort' => 'Sortierung',
        'assets_count' => 'Assets',
        'total_area' => 'Gesamtfläche',
        'rooms_count' => 'Räume',
        'nodes_count' => 'Strukturelemente',
        'types' => [
            'building' => 'Gebäude',
            'floor' => 'Etage',
            'unit' => 'Nutzungseinheit',
            'room' => 'Raum',
            'zone' => 'Zone/Bereich',
            'outdoor' => 'Außenanlage',
        ],
    ],

    'bim' => [
        'title' => 'BIM — Objekt :property',
        'upload' => 'IFC-Modell hochladen',
        'file' => 'IFC-Datei',
        'ifc_guid' => 'IFC-GUID',
        'file_help' => 'IFC 2x3 oder IFC4. Große Dateien können einen Moment brauchen.',
        'file_extension' => 'Es sind nur IFC-Dateien (.ifc) erlaubt.',
        'upload_rejected' => 'Der Upload wurde abgelehnt, weil der Dateipfad ungültig ist.',
        'uploaded' => 'BIM-Modell hochgeladen',
        'uploaded_at' => 'Hochgeladen am',
        'empty' => 'Für dieses Objekt wurde noch kein BIM-Modell hochgeladen. Nutze „IFC-Modell hochladen" oben rechts.',
        'open_full' => 'Vollansicht öffnen',
        'show_in_model' => 'Im BIM-Modell zeigen',
        'inspector' => [
            'title' => 'Bauteil',
            'linked_asset' => 'Verknüpftes Asset',
            'linked_node' => 'Verknüpftes Strukturelement',
            'no_link' => 'Kein verknüpftes Element im CAFM — du kannst dieses Bauteil direkt als Asset anlegen.',
            'open_asset' => 'Asset öffnen',
            'open_structure' => 'Struktur öffnen',
            'create_asset' => 'Als Asset anlegen',
            'asset_created' => 'Asset aus BIM-Bauteil angelegt',
        ],
    ],

    'map' => [
        'heading' => 'Objekte auf der Karte',
        'open_tickets' => ':count offene Störungen',
        'overdue_orders' => ':count überfällige Prüfungen/Wartungen',
    ],

    'room_use' => [
        'sales_floor' => 'Verkaufsraum',
        'storage' => 'Lager',
        'office' => 'Büro',
        'technical' => 'Technikraum',
        'sanitary' => 'Sanitärraum',
        'staff_room' => 'Sozialraum',
        'entrance' => 'Eingang/Windfang',
        'corridor' => 'Flur',
        'outdoor' => 'Außenbereich',
        'waste_collection' => 'Müllplatz / Sammelstelle',
        'other' => 'Sonstiges',
    ],

    'tabs' => [
        'mine' => 'Meine Objekte',
        'all' => 'Alle Objekte',
    ],

    'usage_type' => [
        'commercial' => 'Gewerbe',
        'residential' => 'Wohnen',
        'mixed' => 'Gemischt',
    ],

];
