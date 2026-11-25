<?php

return [

    'model' => 'Contract',
    'models' => 'Contracts',
    'navigation' => 'Contracts',

    'sections' => [
        'master_data' => 'Contract details',
        'term' => 'Term & termination',
        'costs' => 'Costs',
        'assignment' => 'Assignment & documents',
    ],

    'fields' => [
        'name' => 'Name',
        'contract_number' => 'Contract number',
        'partner_name' => 'Contract partner',
        'type' => 'Contract type',
        'status' => 'Status',
        'start_date' => 'Start date',
        'end_date' => 'End date',
        'end_date_help' => 'Leave empty for open-ended contracts.',
        'open_ended' => 'open-ended',
        'notice_period_days' => 'Notice period (days)',
        'notice_period_days_help' => 'Period before the end date within which notice must be given.',
        'reminder_lead_days' => 'Reminder lead time (days)',
        'reminder_lead_days_help' => 'A reminder is sent this many days before the termination deadline.',
        'notice_deadline' => 'Termination deadline',
        'amount' => 'Amount',
        'billing_cycle' => 'Billing cycle',
        'properties' => 'Properties',
        'assets' => 'Assets',
        'notes' => 'Notes',
    ],

    'type' => [
        'maintenance' => 'Maintenance contract',
        'service' => 'Service contract',
        'rental' => 'Lease / rental',
        'insurance' => 'Insurance',
        'utility' => 'Utilities (power/water/…)',
        'waste_disposal' => 'Waste disposal',
        'other' => 'Other',
    ],

    'status' => [
        'active' => 'Active',
        'terminated' => 'Terminated',
        'expired' => 'Expired',
    ],

    'billing_cycle' => [
        'monthly' => 'Monthly',
        'quarterly' => 'Quarterly',
        'yearly' => 'Yearly',
        'one_time' => 'One-time',
    ],

    'reminder' => [
        'title' => 'Notice period running out: :name',
        'body' => 'Contract with :partner — notice can be given until :deadline (contract ends :end). Review and decide now.',
    ],

    'stats' => [
        'active' => 'Active contracts',
        'needs_decision' => 'Termination deadline approaching',
        'yearly_costs' => 'Costs per year',
    ],

];
