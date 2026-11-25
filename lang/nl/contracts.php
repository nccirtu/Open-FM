<?php

return [

    'model' => 'Contract',
    'models' => 'Contracten',
    'navigation' => 'Contracten',

    'sections' => [
        'master_data' => 'Contractgegevens',
        'term' => 'Looptijd & opzegging',
        'costs' => 'Kosten',
        'assignment' => 'Toewijzing & documenten',
    ],

    'fields' => [
        'name' => 'Naam',
        'contract_number' => 'Contractnummer',
        'partner_name' => 'Contractpartner',
        'type' => 'Contracttype',
        'status' => 'Status',
        'start_date' => 'Ingangsdatum',
        'end_date' => 'Einddatum',
        'end_date_help' => 'Leeg laten bij contracten voor onbepaalde tijd.',
        'open_ended' => 'onbepaalde tijd',
        'notice_period_days' => 'Opzegtermijn (dagen)',
        'notice_period_days_help' => 'Termijn vóór het einde van het contract waarbinnen moet worden opgezegd.',
        'reminder_lead_days' => 'Herinneringstermijn (dagen)',
        'reminder_lead_days_help' => 'Zoveel dagen vóór de uiterste opzegdatum wordt er herinnerd.',
        'notice_deadline' => 'Uiterste opzegdatum',
        'amount' => 'Bedrag',
        'billing_cycle' => 'Factureringsperiode',
        'properties' => 'Objecten',
        'assets' => 'Assets',
        'notes' => 'Opmerkingen',
    ],

    'type' => [
        'maintenance' => 'Onderhoudscontract',
        'service' => 'Dienstverleningscontract',
        'rental' => 'Huurcontract',
        'insurance' => 'Verzekering',
        'utility' => 'Nutsvoorziening (stroom/water/…)',
        'waste_disposal' => 'Afvalverwerking',
        'other' => 'Overig',
    ],

    'status' => [
        'active' => 'Actief',
        'terminated' => 'Opgezegd',
        'expired' => 'Verlopen',
    ],

    'billing_cycle' => [
        'monthly' => 'Maandelijks',
        'quarterly' => 'Per kwartaal',
        'yearly' => 'Jaarlijks',
        'one_time' => 'Eenmalig',
    ],

    'reminder' => [
        'title' => 'Opzegtermijn loopt: :name',
        'body' => 'Contract met :partner — opzeggen kan nog tijdig tot :deadline (einddatum :end). Nu controleren en beslissen.',
    ],

    'stats' => [
        'active' => 'Actieve contracten',
        'needs_decision' => 'Uiterste opzegdatum nadert',
        'yearly_costs' => 'Kosten per jaar',
    ],

];
