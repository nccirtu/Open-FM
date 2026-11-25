<?php

return [

    'model' => 'Obiect',
    'models' => 'Obiecte',

    'qr' => [
        'print_structure' => 'Etichete QR structură',
        'print_assets' => 'Etichete QR active',
    ],

    'actions' => [
        'create_modal_heading' => 'Creează un obiect nou',
    ],

    'validation' => [
        'unique_number' => 'Există deja un obiect cu acest număr de obiect.',
    ],

    'sections' => [
        'master' => 'Date de bază',
        'overview' => 'Prezentare generală',
        'areas' => 'Suprafețe & Date despre clădire',
        'location_energy' => 'Amplasare & Energie',
    ],

    'fields' => [
        'usage_type' => 'Tip de utilizare',
        'usage_type_help' => 'Determină indicatorii și obligațiile din zona Energie.',
        'construction_year' => 'Anul construcției',
        'construction_year_help' => 'Pentru avertizări privind substanțele periculoase înainte de demolare (de ex. azbest înainte de 1993).',
        'manager' => 'Responsabil',
        'open_tickets' => 'Defecțiuni deschise',
        'property_number' => 'Număr obiect',
        'street' => 'Strada și numărul',
        'zip_code' => 'Cod poștal',
        'city' => 'Oraș',
        'state' => 'Județ',
        'country' => 'Țară',
        'gross_area' => 'Suprafață brută (m²)',
        'net_area' => 'Suprafață netă (m²)',
        'cleanable_area' => 'Suprafață de curățat (m²)',
        'heated_volume' => 'Volum încălzit (m³)',
        'latitude' => 'Latitudine',
        'longitude' => 'Longitudine',
        'energy_rating' => 'Clasă de eficiență energetică',
        'energy_cert_valid_until' => 'Certificat energetic valabil până la',
    ],

    'structure' => [
        'model' => 'Element de structură',
        'models' => 'Structură',
        'location' => 'Amplasare',
        'name' => 'Denumire',
        'type' => 'Tip',
        'parent' => 'Element superior',
        'level' => 'Nivel',
        'level_help' => '0 = parter, negativ = subsol',
        'room_use' => 'Tip de utilizare',
        'area' => 'Suprafață (m²)',
        'height' => 'Înălțime (m)',
        'heated' => 'Încălzit',
        'sort' => 'Sortare',
        'assets_count' => 'Assets',
        'total_area' => 'Suprafață totală',
        'rooms_count' => 'Încăperi',
        'nodes_count' => 'Elemente de structură',
        'types' => [
            'building' => 'Clădire',
            'floor' => 'Etaj',
            'unit' => 'Unitate de utilizare',
            'room' => 'Încăpere',
            'zone' => 'Zonă/Sector',
            'outdoor' => 'Amenajare exterioară',
        ],
    ],

    'bim' => [
        'title' => 'BIM — Obiect :property',
        'upload' => 'Încarcă model IFC',
        'file' => 'Fișier IFC',
        'ifc_guid' => 'GUID IFC',
        'file_help' => 'IFC 2x3 sau IFC4. Fișierele mari pot dura un moment.',
        'file_extension' => 'Sunt permise doar fișiere IFC (.ifc).',
        'upload_rejected' => 'Încărcarea a fost respinsă deoarece calea fișierului este invalidă.',
        'uploaded' => 'Model BIM încărcat',
        'uploaded_at' => 'Încărcat la',
        'empty' => 'Pentru acest obiect nu a fost încărcat încă niciun model BIM. Folosiți „Încarcă model IFC" sus în dreapta.',
        'open_full' => 'Deschide vizualizarea completă',
        'show_in_model' => 'Arată în modelul BIM',
        'inspector' => [
            'title' => 'Element de construcție',
            'linked_asset' => 'Asset asociat',
            'linked_node' => 'Element de structură asociat',
            'no_link' => 'Niciun element asociat în CAFM — puteți crea acest element de construcție direct ca asset.',
            'open_asset' => 'Deschide assetul',
            'open_structure' => 'Deschide structura',
            'create_asset' => 'Creează ca asset',
            'asset_created' => 'Asset creat din elementul de construcție BIM',
        ],
    ],

    'map' => [
        'heading' => 'Obiecte pe hartă',
        'open_tickets' => ':count defecțiuni deschise',
        'overdue_orders' => ':count verificări/mentenanțe restante',
    ],

    'room_use' => [
        'sales_floor' => 'Spațiu de vânzare',
        'storage' => 'Depozit',
        'office' => 'Birou',
        'technical' => 'Încăpere tehnică',
        'sanitary' => 'Grup sanitar',
        'staff_room' => 'Sală de personal',
        'entrance' => 'Intrare/Vestibul',
        'corridor' => 'Coridor',
        'outdoor' => 'Zonă exterioară',
        'waste_collection' => 'Punct de colectare a deșeurilor',
        'other' => 'Altele',
    ],

    'tabs' => [
        'mine' => 'Obiectele mele',
        'all' => 'Toate obiectele',
    ],

    'usage_type' => [
        'commercial' => 'Comercial',
        'residential' => 'Rezidențial',
        'mixed' => 'Mixt',
    ],

];
