<?php

return [

    'child_assets' => 'Sub-assets',
    'overview' => 'Overview',
    'sections' => [
        'location' => 'Location in building',
        'lifecycle' => 'Lifecycle',
    ],

    'tabs' => [
        'general' => 'General',
        'maintenance' => 'Maintenance & lifecycle',
    ],

    'actions' => [
        'new' => 'New',
        'open' => 'Open',
        'create_asset_type_heading' => 'Create new asset type',
        'create_company_heading' => 'Create new company',
        'create_inspection_company_heading' => 'Create new inspection company',
    ],

    'fields' => [
        'id' => 'ID',
        'name' => 'Name',
        'image' => 'Image',
        'asset_number' => 'Asset no.',
        'type' => 'Type',
        'manufacturer' => 'Manufacturer',
        'serial_number' => 'Serial number',
        'serial_number_short' => 'Serial no.',
        'maintenance_partner' => 'Maintenance partner',
        'inspection_company' => 'Inspection company',
        'last_maintenance' => 'Last maintenance',
        'next_maintenance' => 'Next maintenance',
        'kategorie' => 'Category',
        'gruppe' => 'Group',
        'location' => 'Location',
        'parent_asset' => 'Parent asset',
        'condition' => 'Condition',
        'service_status' => 'Service status',
        'warranty_until' => 'Warranty until',
        'installed_at' => 'Installed on',
        'decommissioned_at' => 'Decommissioned on',
        'purchase_price' => 'Purchase price',
        'category' => 'Trade',
    ],

    'condition' => [
        'good' => 'Good',
        'fair' => 'Fair',
        'poor' => 'Poor',
    ],

    'service_status' => [
        'in_operation' => 'In operation',
        'out_of_operation' => 'Out of operation',
        'in_storage' => 'In storage',
        'disposed' => 'Disposed',
    ],

    'filters' => [
        'all' => 'All',
        'include_sub_locations' => 'incl. sub-locations',
        'classification' => 'Classification',
        'maintenance_overdue' => 'Overdue',
        'due_within' => 'Due within :days days',
        'maintenance_none' => 'No maintenance planned',
        'warranty_expired' => 'Expired',
        'warranty_expiring' => 'Expires within 90 days',
        'warranty_valid' => 'Valid',
        'not_specified' => 'Not specified',
        'installed_from' => 'Installed from',
        'installed_until' => 'Installed until',
        'hierarchy' => 'Asset hierarchy',
        'top_level_only' => 'Top-level assets only',
        'sub_assets_only' => 'Sub-assets only',
        'decommissioned' => 'Decommissioned',
        'active' => 'In use',
        'bim' => 'BIM model',
        'bim_linked' => 'Linked to a BIM element',
        'bim_not_linked' => 'Not linked to BIM',
    ],

    'import' => [
        'help' => [
            'asset_number' => 'Only used to update existing assets (option “Update existing assets”). New assets are numbered automatically.',
            'property' => 'Property number.',
            'location' => 'Name or path of the location within the property, e.g. “Building A › GF › Corridor” (“/” or “>” work too).',
            'parent_asset' => 'Asset number or name of the parent asset in the same property.',
        ],
        'options' => [
            'update_existing' => 'Update existing assets by asset number (empty cells keep current values)',
            'create_missing' => 'Create unknown asset types and companies automatically',
        ],
        'template' => [
            'action' => 'Import template',
            'heading' => 'Download import template',
            'description' => 'CSV file with all standard columns. Select asset types to also get their type-specific fields (e.g. fill quantity for fire extinguishers) as columns. Additional fields are recognised automatically by their column header during import.',
            'download' => 'Download',
            'asset_types' => 'Asset types with additional fields',
        ],
        'completed_title' => 'Asset import completed',
        'completed_body' => '{0} No asset was imported.|{1} :count asset was imported.|[2,*] :count assets were imported.',
        'failed_rows' => '{1} :count row failed.|[2,*] :count rows failed.',
        'errors' => [
            'no_organization' => 'No organisation set for the import.',
            'property_not_found' => 'Property “:value” not found.',
            'location_without_property' => 'Location “:value” cannot be assigned without a property.',
            'location_not_found' => 'Location “:value” not found in the property.',
            'location_ambiguous' => 'Location “:value” is ambiguous – please provide the path (e.g. “Building A › GF › Corridor”).',
            'asset_type_not_found' => 'Asset type “:value” not found.',
            'parent_not_found' => 'Parent asset “:value” not found.',
            'company_not_found' => 'Company “:value” not found.',
            'invalid_number' => '“:value” is not a valid number.',
            'invalid_boolean' => '“:value” is not a valid yes/no value.',
            'invalid_date' => '“:value” is not a valid date.',
            'invalid_option' => '“:value” is not a valid option.',
        ],
    ],

];
