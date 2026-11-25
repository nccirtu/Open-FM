<?php

return [

    'model' => 'Immobile',
    'models' => 'Immobili',

    'qr' => [
        'print_structure' => 'Etichette QR struttura',
        'print_assets' => 'Etichette QR asset',
    ],

    'actions' => [
        'create_modal_heading' => 'Crea nuovo immobile',
    ],

    'validation' => [
        'unique_number' => 'Esiste già un immobile con questo numero immobile.',
    ],

    'sections' => [
        'master' => 'Dati anagrafici',
        'overview' => 'Panoramica',
        'areas' => 'Superfici e dati dell\'edificio',
        'location_energy' => 'Posizione ed energia',
    ],

    'fields' => [
        'usage_type' => 'Destinazione d’uso',
        'usage_type_help' => 'Determina indicatori e obblighi nell’area Energia.',
        'construction_year' => 'Anno di costruzione',
        'construction_year_help' => 'Per gli avvisi sulle sostanze pericolose prima della demolizione (ad es. amianto prima del 1993).',
        'manager' => 'Referente',
        'open_tickets' => 'Guasti aperti',
        'property_number' => 'Numero immobile',
        'street' => 'Via e numero civico',
        'zip_code' => 'CAP',
        'city' => 'Città',
        'state' => 'Provincia',
        'country' => 'Paese',
        'gross_area' => 'Superficie lorda (m²)',
        'net_area' => 'Superficie netta (m²)',
        'cleanable_area' => 'Superficie da pulire (m²)',
        'heated_volume' => 'Volume riscaldato (m³)',
        'latitude' => 'Latitudine',
        'longitude' => 'Longitudine',
        'energy_rating' => 'Classe di efficienza energetica',
        'energy_cert_valid_until' => 'Attestato energetico valido fino al',
    ],

    'structure' => [
        'model' => 'Elemento di struttura',
        'models' => 'Struttura',
        'location' => 'Posizione',
        'name' => 'Denominazione',
        'type' => 'Tipo',
        'parent' => 'Elemento sovraordinato',
        'level' => 'Livello',
        'level_help' => '0 = piano terra, negativo = piano interrato',
        'room_use' => 'Destinazione d\'uso',
        'area' => 'Superficie (m²)',
        'height' => 'Altezza (m)',
        'heated' => 'Riscaldato',
        'sort' => 'Ordinamento',
        'assets_count' => 'Asset',
        'total_area' => 'Superficie totale',
        'rooms_count' => 'Locali',
        'nodes_count' => 'Elementi di struttura',
        'types' => [
            'building' => 'Edificio',
            'floor' => 'Piano',
            'unit' => 'Unità d\'uso',
            'room' => 'Locale',
            'zone' => 'Zona/area',
            'outdoor' => 'Area esterna',
        ],
    ],

    'bim' => [
        'title' => 'BIM — Immobile :property',
        'upload' => 'Carica modello IFC',
        'file' => 'File IFC',
        'ifc_guid' => 'GUID IFC',
        'file_help' => 'IFC 2x3 o IFC4. I file di grandi dimensioni possono richiedere qualche istante.',
        'file_extension' => 'Sono consentiti solo file IFC (.ifc).',
        'upload_rejected' => 'Il caricamento è stato rifiutato perché il percorso del file non è valido.',
        'uploaded' => 'Modello BIM caricato',
        'uploaded_at' => 'Caricato il',
        'empty' => 'Per questo immobile non è stato ancora caricato alcun modello BIM. Usa “Carica modello IFC” in alto a destra.',
        'open_full' => 'Apri vista completa',
        'show_in_model' => 'Mostra nel modello BIM',
        'inspector' => [
            'title' => 'Componente',
            'linked_asset' => 'Asset collegato',
            'linked_node' => 'Elemento di struttura collegato',
            'no_link' => 'Nessun elemento collegato nel CAFM — puoi creare direttamente questo componente come asset.',
            'open_asset' => 'Apri asset',
            'open_structure' => 'Apri struttura',
            'create_asset' => 'Crea come asset',
            'asset_created' => 'Asset creato dal componente BIM',
        ],
    ],

    'map' => [
        'heading' => 'Immobili sulla mappa',
        'open_tickets' => ':count guasti aperti',
        'overdue_orders' => ':count ispezioni/manutenzioni scadute',
    ],

    'room_use' => [
        'sales_floor' => 'Area vendita',
        'storage' => 'Magazzino',
        'office' => 'Ufficio',
        'technical' => 'Locale tecnico',
        'sanitary' => 'Servizi igienici',
        'staff_room' => 'Locale per il personale',
        'entrance' => 'Ingresso/bussola',
        'corridor' => 'Corridoio',
        'outdoor' => 'Area esterna',
        'waste_collection' => 'Punto di raccolta rifiuti',
        'other' => 'Altro',
    ],

    'tabs' => [
        'mine' => 'I miei immobili',
        'all' => 'Tutti gli immobili',
    ],

    'usage_type' => [
        'commercial' => 'Commerciale',
        'residential' => 'Residenziale',
        'mixed' => 'Mista',
    ],

];
