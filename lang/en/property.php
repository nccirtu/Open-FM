<?php

return [

    'model' => 'Property',
    'models' => 'Properties',

    'qr' => [
        'print_structure' => 'QR labels structure',
        'print_assets' => 'QR labels assets',
    ],

    'actions' => [
        'create_modal_heading' => 'Create new property',
    ],

    'validation' => [
        'unique_number' => 'A property with this number already exists.',
    ],

    'sections' => [
        'master' => 'Master data',
        'overview' => 'Overview',
        'areas' => 'Areas & building data',
        'location_energy' => 'Location & energy',
    ],

    'fields' => [
        'usage_type' => 'Usage type',
        'usage_type_help' => 'Determines key figures and obligations in the energy area.',
        'construction_year' => 'Year of construction',
        'construction_year_help' => 'Used for hazardous material hints before demolition (e.g. asbestos before 1993).',
        'manager' => 'Property manager',
        'open_tickets' => 'Open tickets',
        'property_number' => 'Property number',
        'street' => 'Street and number',
        'zip_code' => 'ZIP code',
        'city' => 'City',
        'state' => 'State',
        'country' => 'Country',
        'gross_area' => 'Gross area (m²)',
        'net_area' => 'Net area (m²)',
        'cleanable_area' => 'Cleanable area (m²)',
        'heated_volume' => 'Heated volume (m³)',
        'latitude' => 'Latitude',
        'longitude' => 'Longitude',
        'energy_rating' => 'Energy efficiency rating',
        'energy_cert_valid_until' => 'Energy certificate valid until',
    ],

    'structure' => [
        'model' => 'Structure element',
        'models' => 'Structure',
        'location' => 'Location',
        'name' => 'Name',
        'type' => 'Type',
        'parent' => 'Parent element',
        'level' => 'Level',
        'level_help' => '0 = ground floor, negative = basement',
        'room_use' => 'Room use',
        'area' => 'Area (m²)',
        'height' => 'Height (m)',
        'heated' => 'Heated',
        'sort' => 'Sort order',
        'assets_count' => 'Assets',
        'total_area' => 'Total area',
        'rooms_count' => 'Rooms',
        'nodes_count' => 'Structure elements',
        'types' => [
            'building' => 'Building',
            'floor' => 'Floor',
            'unit' => 'Unit',
            'room' => 'Room',
            'zone' => 'Zone/Area',
            'outdoor' => 'Outdoor area',
        ],
    ],

    'bim' => [
        'title' => 'BIM — Property :property',
        'upload' => 'Upload IFC model',
        'file' => 'IFC file',
        'ifc_guid' => 'IFC GUID',
        'file_help' => 'IFC 2x3 or IFC4. Large files may take a moment.',
        'file_extension' => 'Only IFC files (.ifc) are allowed.',
        'upload_rejected' => 'The upload was rejected because the file path is invalid.',
        'uploaded' => 'BIM model uploaded',
        'uploaded_at' => 'Uploaded at',
        'empty' => 'No BIM model has been uploaded for this property yet. Use "Upload IFC model" in the top right corner.',
        'open_full' => 'Open full view',
        'show_in_model' => 'Show in BIM model',
        'inspector' => [
            'title' => 'Element',
            'linked_asset' => 'Linked asset',
            'linked_node' => 'Linked structure element',
            'no_link' => 'No linked CAFM element — you can create an asset from this element directly.',
            'open_asset' => 'Open asset',
            'open_structure' => 'Open structure',
            'create_asset' => 'Create as asset',
            'asset_created' => 'Asset created from BIM element',
        ],
    ],

    'map' => [
        'heading' => 'Properties on the map',
        'open_tickets' => ':count open tickets',
        'overdue_orders' => ':count overdue inspections/maintenance',
    ],

    'room_use' => [
        'sales_floor' => 'Sales floor',
        'storage' => 'Storage',
        'office' => 'Office',
        'technical' => 'Technical room',
        'sanitary' => 'Sanitary room',
        'staff_room' => 'Staff room',
        'entrance' => 'Entrance',
        'corridor' => 'Corridor',
        'outdoor' => 'Outdoor area',
        'waste_collection' => 'Waste collection point',
        'other' => 'Other',
    ],

    'tabs' => [
        'mine' => 'My properties',
        'all' => 'All properties',
    ],

    'usage_type' => [
        'commercial' => 'Commercial',
        'residential' => 'Residential',
        'mixed' => 'Mixed use',
    ],

];
