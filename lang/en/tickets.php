<?php

return [

    'model' => 'Ticket',
    'models' => 'Tickets',
    'navigation' => 'Tickets',

    'fields' => [
        'ticket_number' => 'Ticket no.',
        'property' => 'Property / Property',
        'asset' => 'Affected asset',
        'type' => 'Type',
        'category' => 'Category',
        'priority' => 'Priority',
        'status' => 'Status',
        'title' => 'Title',
        'description' => 'Description',
        'photos' => 'Photos',
        'reporter' => 'Reported by',
        'assignee' => 'Assigned to',
        'company' => 'Service partner',
        'due_at' => 'Due at',
        'resolved_at' => 'Resolved at',
        'resolution_notes' => 'Resolution notes',
        'created_at' => 'Reported at',
        'suspension_reason' => 'Waiting reason',
        'estimate_status' => 'Cost estimate',
        'estimate_amount' => 'Quoted amount',
        'sla' => 'SLA deadline',
    ],

    'sections' => [
        'report' => 'Report',
        'processing' => 'Processing',
    ],

    'type' => [
        'fault' => 'Fault',
        'defect' => 'Defect',
        'request' => 'Request',
    ],

    'category' => [
        'electrical' => 'Electrical',
        'plumbing' => 'Plumbing',
        'hvac' => 'Heating / Ventilation / AC',
        'refrigeration' => 'Refrigeration',
        'building' => 'Building / Structural',
        'safety' => 'Safety / Fire protection',
        'cleaning' => 'Cleaning',
        'other' => 'Other',
    ],

    'priority' => [
        'p1' => 'P1 — Emergency',
        'p2' => 'P2 — Urgent',
        'p3' => 'P3 — Standard',
    ],

    'status' => [
        'reported' => 'Reported',
        'accepted' => 'Accepted',
        'in_progress' => 'In progress',
        'waiting' => 'Waiting',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
    ],

    'suspension_reason' => [
        'awaiting_parts' => 'Awaiting spare parts',
        'awaiting_partner' => 'Awaiting service partner',
        'other' => 'Other',
    ],

    'estimate_status' => [
        'requested' => 'Requested',
        'received' => 'Received',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ],

    'sla' => [
        'model' => 'SLA rule',
        'models' => 'SLA rules',
        'navigation' => 'SLA rules',
        'response_hours' => 'Response time (hours)',
        'response_hours_help' => 'Deadline for the first agent reply.',
        'resolution_hours' => 'Resolution time (hours)',
        'resolution_hours_help' => 'Deadline for resolution; the "Waiting" status pauses the clock.',
        'priority_unique' => 'An SLA rule already exists for this priority.',
        'escalation' => [
            'title' => 'SLA breached',
            'body' => 'Ticket ":title" has exceeded its SLA deadline (resolution due: :due).',
        ],
    ],

    'filters' => [
        'open' => 'Open only',
    ],

    'reporter_stores' => 'Properties (reporter assignment)',
    'reporter_stores_help' => 'Reporters only see these properties in the service portal; without an assignment they see all properties of their organization.',

    'service_partner_help' => 'For service-partner logins: users with the "Dienstleister" role only see tickets and maintenance orders of this company.',

    'new' => 'NEW',

    'replies' => [
        'title' => 'History & replies',
        'message' => 'Message',
        'attachments' => 'Attachments',
        'internal_note' => 'Internal note',
        'internal_note_help' => 'Internal notes are never visible to reporters in the tenant portal.',
        'author' => 'From',
        'created_at' => 'Date',
        'create' => 'Add reply',
    ],

    'activities' => [
        'title' => 'Activity',
    ],

    'timeline' => [
        'automation' => 'Automation ":rule" was executed.',
    ],

    'actions' => [
        'label' => 'Actions',
    ],

    'process' => [
        'action' => 'Processing',
        'heading' => 'Process ticket',
        'submit' => 'Save',
    ],

    'stats' => [
        'open' => 'Open tickets',
        'p1_open' => 'Open emergencies (P1)',
        'overdue' => 'Overdue',
        'resolved_30d' => 'Resolved (30 days)',
    ],

    'automations' => [
        'model' => 'Automation rule',
        'models' => 'Automation rules',
        'navigation' => 'Automations',
        'name' => 'Name',
        'is_active' => 'Active',
        'event' => 'Event',
        'events' => [
            'created' => 'Ticket reported',
            'status_changed' => 'Status changed',
            'assigned' => 'Ticket assigned',
            'reply_added' => 'Reply added',
        ],
        'conditions' => 'Conditions (all must match)',
        'condition_field' => 'Field',
        'condition_value' => 'Value',
        'condition_fields' => [
            'category' => 'Category',
            'priority' => 'Priority',
            'type' => 'Type',
            'status' => 'Status',
        ],
        'actions' => 'Actions',
        'action_type' => 'Action',
        'action_value' => 'Value',
        'action_note' => 'Note text',
        'action_types' => [
            'assign_to' => 'Assign ticket to',
            'set_status' => 'Set status',
            'set_priority' => 'Set priority',
            'add_internal_note' => 'Add internal note',
        ],
    ],

    'portal' => [
        'navigation' => 'My reports',
        'report' => 'Report an issue',
        'report_for_asset' => 'Report an issue',
        'confirm' => [
            'action' => 'Confirm resolution',
            'heading' => 'Confirm resolution',
            'submit' => 'Confirm & close',
            'rating' => 'Satisfaction',
            'rating_help' => 'How satisfied are you with the resolution? (optional)',
        ],
        'reopen' => [
            'action' => 'Reopen',
            'heading' => 'Reopen this ticket',
            'submit' => 'Reopen',
            'reason' => 'What is still wrong?',
            'reply_prefix' => 'Ticket reopened:',
        ],
    ],

    'notifications' => [
        'assigned' => [
            'title' => 'Ticket assigned',
            'body' => 'The ticket ":title" (:property) has been assigned to you.',
        ],
        'status' => [
            'title' => 'Status of your ticket changed',
            'body' => 'Your ticket ":title" is now ":status".',
        ],
        'reply' => [
            'title' => 'New reply',
            'body' => ':author replied to the ticket ":title".',
        ],
    ],

];
