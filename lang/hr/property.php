<?php

return [

    'model' => 'Objekt',
    'models' => 'Objekti',

    'qr' => [
        'print_structure' => 'QR naljepnice struktura',
        'print_assets' => 'QR naljepnice imovina',
    ],

    'actions' => [
        'create_modal_heading' => 'Izradi novi objekt',
    ],

    'validation' => [
        'unique_number' => 'Objekt s ovim brojem objekta već postoji.',
    ],

    'sections' => [
        'master' => 'Matični podaci',
        'overview' => 'Pregled',
        'areas' => 'Površine i podaci o zgradi',
        'location_energy' => 'Lokacija i energija',
    ],

    'fields' => [
        'usage_type' => 'Namjena',
        'usage_type_help' => 'Određuje pokazatelje i obveze u području energije.',
        'construction_year' => 'Godina izgradnje',
        'construction_year_help' => 'Za upozorenja o štetnim tvarima prije rušenja (npr. azbest prije 1993.).',
        'manager' => 'Odgovorna osoba',
        'open_tickets' => 'Otvoreni kvarovi',
        'property_number' => 'Broj objekta',
        'street' => 'Ulica i kućni broj',
        'zip_code' => 'Poštanski broj',
        'city' => 'Grad',
        'state' => 'Županija',
        'country' => 'Država',
        'gross_area' => 'Bruto površina (m²)',
        'net_area' => 'Neto površina (m²)',
        'cleanable_area' => 'Površina za čišćenje (m²)',
        'heated_volume' => 'Grijani volumen (m³)',
        'latitude' => 'Zemljopisna širina',
        'longitude' => 'Zemljopisna dužina',
        'energy_rating' => 'Energetski razred',
        'energy_cert_valid_until' => 'Energetski certifikat vrijedi do',
    ],

    'structure' => [
        'model' => 'Strukturni element',
        'models' => 'Struktura',
        'location' => 'Lokacija',
        'name' => 'Naziv',
        'type' => 'Tip',
        'parent' => 'Nadređeni element',
        'level' => 'Razina',
        'level_help' => '0 = prizemlje, negativno = podrum',
        'room_use' => 'Namjena',
        'area' => 'Površina (m²)',
        'height' => 'Visina (m)',
        'heated' => 'Grijano',
        'sort' => 'Redoslijed',
        'assets_count' => 'Sredstva',
        'total_area' => 'Ukupna površina',
        'rooms_count' => 'Prostorije',
        'nodes_count' => 'Strukturni elementi',
        'types' => [
            'building' => 'Zgrada',
            'floor' => 'Etaža',
            'unit' => 'Uporabna jedinica',
            'room' => 'Prostorija',
            'zone' => 'Zona/područje',
            'outdoor' => 'Vanjski prostor',
        ],
    ],

    'bim' => [
        'title' => 'BIM — objekt :property',
        'upload' => 'Prenesi IFC model',
        'file' => 'IFC datoteka',
        'ifc_guid' => 'IFC GUID',
        'file_help' => 'IFC 2x3 ili IFC4. Velike datoteke mogu potrajati koji trenutak.',
        'file_extension' => 'Dopuštene su samo IFC datoteke (.ifc).',
        'upload_rejected' => 'Prijenos je odbijen jer je putanja datoteke neispravna.',
        'uploaded' => 'BIM model prenesen',
        'uploaded_at' => 'Preneseno',
        'empty' => 'Za ovaj objekt još nije prenesen BIM model. Koristite „Prenesi IFC model“ gore desno.',
        'open_full' => 'Otvori puni prikaz',
        'show_in_model' => 'Prikaži u BIM modelu',
        'inspector' => [
            'title' => 'Građevni element',
            'linked_asset' => 'Povezano sredstvo',
            'linked_node' => 'Povezani strukturni element',
            'no_link' => 'Nema povezanog elementa u CAFM-u — ovaj građevni element možete izravno izraditi kao sredstvo.',
            'open_asset' => 'Otvori sredstvo',
            'open_structure' => 'Otvori strukturu',
            'create_asset' => 'Izradi kao sredstvo',
            'asset_created' => 'Sredstvo izrađeno iz BIM elementa',
        ],
    ],

    'map' => [
        'heading' => 'Objekti na karti',
        'open_tickets' => ':count otvorenih kvarova',
        'overdue_orders' => ':count ispitivanja/održavanja u kašnjenju',
    ],

    'room_use' => [
        'sales_floor' => 'Prodajni prostor',
        'storage' => 'Skladište',
        'office' => 'Ured',
        'technical' => 'Tehnička prostorija',
        'sanitary' => 'Sanitarni prostor',
        'staff_room' => 'Prostorija za osoblje',
        'entrance' => 'Ulaz/vjetrobran',
        'corridor' => 'Hodnik',
        'outdoor' => 'Vanjski prostor',
        'waste_collection' => 'Mjesto za otpad',
        'other' => 'Ostalo',
    ],

    'tabs' => [
        'mine' => 'Moji objekti',
        'all' => 'Svi objekti',
    ],

    'usage_type' => [
        'commercial' => 'Poslovno',
        'residential' => 'Stambeno',
        'mixed' => 'Mješovito',
    ],

];
