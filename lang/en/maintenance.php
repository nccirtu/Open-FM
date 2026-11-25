<?php

return [

    'navigation_group' => 'Maintenance & Inspection',

    'plans' => [
        'model' => 'Maintenance plan',
        'models' => 'Maintenance plans',
        'navigation' => 'Maintenance plans',
        'generate_order' => 'Generate order',
        'order_created' => 'Maintenance order created',
        'order_exists' => 'An order for this due date already exists',
    ],

    'orders' => [
        'model' => 'Maintenance order',
        'models' => 'Maintenance orders',
        'navigation' => 'Maintenance orders',
        'filters' => [
            'overdue' => 'Overdue only',
        ],
        'complete' => [
            'action' => 'Complete',
            'heading' => 'Complete order',
            'submit' => 'Complete',
            'tasks' => 'Completed checklist items',
            'tasks_help' => 'Checked items are stored on the order for audit purposes.',
            'protocol' => 'Inspection / maintenance protocol',
            'protocol_help' => 'Supporting documents (PDF, photos) — audit-proof storage on the order.',
            'defect_found' => 'Defect found',
            'defect_found_help' => 'Automatically creates a defect ticket on the affected asset.',
            'defect_title' => 'Defect title',
            'defect_description' => 'Defect description',
            'defect_source' => 'Found during: :plan (:date)',
            'done' => 'Order completed',
        ],
    ],

    'fields' => [
        'asset' => 'Asset',
        'name' => 'Name',
        'name_placeholder' => 'e.g. DGUV V3 inspection',
        'type' => 'Type',
        'legal_basis' => 'Legal basis',
        'legal_basis_placeholder' => 'e.g. DGUV V3, BetrSichV, TrinkwV',
        'interval' => 'Interval',
        'interval_value' => 'Interval (count)',
        'interval_unit' => 'Interval (unit)',
        'next_due_at' => 'Next due date',
        'lead_time_days' => 'Lead time (days)',
        'lead_time_days_help' => 'The order is generated automatically this many days before the due date.',
        'inspection_company' => 'Inspection company',
        'tasks' => 'Checklist items',
        'tasks_help' => 'These items appear as a checklist in the completion modal of every order.',
        'tasks_add' => 'Add checklist item',
        'is_active' => 'Active',
        'notes' => 'Notes',
        'due_at' => 'Due at',
        'status' => 'Status',
        'completed_at' => 'Completed at',
        'completed_by' => 'Completed by',
    ],

    'type' => [
        'maintenance' => 'Maintenance',
        'inspection' => 'Statutory inspection',
    ],

    'interval_unit' => [
        'days' => 'Days',
        'weeks' => 'Weeks',
        'months' => 'Months',
        'years' => 'Years',
    ],

    'order_status' => [
        'open' => 'Open',
        'completed' => 'Completed',
        'skipped' => 'Skipped',
    ],

    'catalogs' => [
        'import_label' => 'Import maintenance catalog',
        'import_help' => 'Imports country-specific statutory inspections and maintenance as templates per asset type.',
        'import_submit' => 'Import',
        'imported' => ':count templates imported.',
        'import_none' => 'No import',
        'countries' => [
            'de' => 'Germany',
            'fr' => 'France',
            'pl' => 'Poland',
        ],
    ],

    'templates' => [
        'model' => 'Maintenance catalog template',
        'models' => 'Maintenance catalog',
        'navigation' => 'Maintenance catalog',
        'asset_type' => 'Asset type',
        'country' => 'Country',
        'plans_count' => 'Generated plans',
    ],

    'stats' => [
        'overdue' => 'Overdue inspections/maintenance',
        'due_30d' => 'Due within 30 days',
        'open' => 'Open orders',
        'active_plans' => 'Active plans',
    ],

];
