<?php

return [

    'model' => 'Object',
    'models' => 'Objecten',

    'qr' => [
        'print_structure' => 'QR-etiketten structuur',
        'print_assets' => 'QR-etiketten assets',
    ],

    'actions' => [
        'create_modal_heading' => 'Nieuw object aanmaken',
    ],

    'validation' => [
        'unique_number' => 'Er bestaat al een object met dit objectnummer.',
    ],

    'sections' => [
        'master' => 'Stamgegevens',
        'overview' => 'Overzicht',
        'areas' => 'Oppervlakten & gebouwgegevens',
        'location_energy' => 'Locatie & energie',
    ],

    'fields' => [
        'usage_type' => 'Gebruiksfunctie',
        'usage_type_help' => 'Bepaalt kengetallen en verplichtingen in het onderdeel Energie.',
        'construction_year' => 'Bouwjaar',
        'construction_year_help' => 'Voor waarschuwingen over schadelijke stoffen bij sloop (bijv. asbest vóór 1993).',
        'manager' => 'Beheerder',
        'open_tickets' => 'Open storingen',
        'property_number' => 'Objectnummer',
        'street' => 'Straat en huisnummer',
        'zip_code' => 'Postcode',
        'city' => 'Plaats',
        'state' => 'Provincie',
        'country' => 'Land',
        'gross_area' => 'Bruto-oppervlakte (m²)',
        'net_area' => 'Netto-oppervlakte (m²)',
        'cleanable_area' => 'Schoonmaakoppervlakte (m²)',
        'heated_volume' => 'Verwarmd volume (m³)',
        'latitude' => 'Breedtegraad',
        'longitude' => 'Lengtegraad',
        'energy_rating' => 'Energielabel',
        'energy_cert_valid_until' => 'Energiecertificaat geldig tot',
    ],

    'structure' => [
        'model' => 'Structuurelement',
        'models' => 'Structuur',
        'location' => 'Locatie',
        'name' => 'Omschrijving',
        'type' => 'Type',
        'parent' => 'Bovenliggend element',
        'level' => 'Verdieping',
        'level_help' => '0 = begane grond, negatief = kelder',
        'room_use' => 'Gebruiksfunctie',
        'area' => 'Oppervlakte (m²)',
        'height' => 'Hoogte (m)',
        'heated' => 'Verwarmd',
        'sort' => 'Sortering',
        'assets_count' => 'Assets',
        'total_area' => 'Totale oppervlakte',
        'rooms_count' => 'Ruimten',
        'nodes_count' => 'Structuurelementen',
        'types' => [
            'building' => 'Gebouw',
            'floor' => 'Verdieping',
            'unit' => 'Gebruikseenheid',
            'room' => 'Ruimte',
            'zone' => 'Zone/gebied',
            'outdoor' => 'Buitenterrein',
        ],
    ],

    'bim' => [
        'title' => 'BIM — object :property',
        'upload' => 'IFC-model uploaden',
        'file' => 'IFC-bestand',
        'ifc_guid' => 'IFC-GUID',
        'file_help' => 'IFC 2x3 of IFC4. Grote bestanden kunnen even duren.',
        'file_extension' => 'Alleen IFC-bestanden (.ifc) zijn toegestaan.',
        'upload_rejected' => 'De upload is geweigerd omdat het bestandspad ongeldig is.',
        'uploaded' => 'BIM-model geüpload',
        'uploaded_at' => 'Geüpload op',
        'empty' => 'Voor dit object is nog geen BIM-model geüpload. Gebruik "IFC-model uploaden" rechtsboven.',
        'open_full' => 'Volledige weergave openen',
        'show_in_model' => 'In BIM-model tonen',
        'inspector' => [
            'title' => 'Bouwdeel',
            'linked_asset' => 'Gekoppeld asset',
            'linked_node' => 'Gekoppeld structuurelement',
            'no_link' => 'Geen gekoppeld element in het CAFM — je kunt dit bouwdeel direct als asset aanmaken.',
            'open_asset' => 'Asset openen',
            'open_structure' => 'Structuur openen',
            'create_asset' => 'Als asset aanmaken',
            'asset_created' => 'Asset aangemaakt uit BIM-bouwdeel',
        ],
    ],

    'map' => [
        'heading' => 'Objecten op de kaart',
        'open_tickets' => ':count open storingen',
        'overdue_orders' => ':count achterstallige keuringen/onderhoudsbeurten',
    ],

    'room_use' => [
        'sales_floor' => 'Verkoopruimte',
        'storage' => 'Magazijn',
        'office' => 'Kantoor',
        'technical' => 'Technische ruimte',
        'sanitary' => 'Sanitaire ruimte',
        'staff_room' => 'Personeelsruimte',
        'entrance' => 'Entree/tochtportaal',
        'corridor' => 'Gang',
        'outdoor' => 'Buitenruimte',
        'waste_collection' => 'Afvalinzamelpunt',
        'other' => 'Overig',
    ],

    'tabs' => [
        'mine' => 'Mijn objecten',
        'all' => 'Alle objecten',
    ],

    'usage_type' => [
        'commercial' => 'Utiliteit',
        'residential' => 'Wonen',
        'mixed' => 'Gemengd',
    ],

];
