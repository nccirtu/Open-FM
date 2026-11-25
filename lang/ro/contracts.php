<?php

return [

    'model' => 'Contract',
    'models' => 'Contracte',
    'navigation' => 'Contracte',

    'sections' => [
        'master_data' => 'Date contractuale',
        'term' => 'Durată & Reziliere',
        'costs' => 'Costuri',
        'assignment' => 'Atribuire & Documente',
    ],

    'fields' => [
        'name' => 'Denumire',
        'contract_number' => 'Număr contract',
        'partner_name' => 'Partener contractual',
        'type' => 'Tip de contract',
        'status' => 'Stare',
        'start_date' => 'Începutul contractului',
        'end_date' => 'Sfârșitul contractului',
        'end_date_help' => 'Lăsați gol pentru contracte pe durată nedeterminată.',
        'open_ended' => 'nedeterminat',
        'notice_period_days' => 'Perioadă de preaviz (zile)',
        'notice_period_days_help' => 'Termenul înainte de sfârșitul contractului în care trebuie transmisă rezilierea.',
        'reminder_lead_days' => 'Avans pentru memento (zile)',
        'reminder_lead_days_help' => 'Cu atâtea zile înainte de data limită de reziliere se trimite mementoul.',
        'notice_deadline' => 'Data limită de reziliere',
        'amount' => 'Sumă',
        'billing_cycle' => 'Perioadă de facturare',
        'properties' => 'Obiecte',
        'assets' => 'Assets',
        'notes' => 'Observații',
    ],

    'type' => [
        'maintenance' => 'Contract de mentenanță',
        'service' => 'Contract de prestări servicii',
        'rental' => 'Contract de închiriere',
        'insurance' => 'Asigurare',
        'utility' => 'Utilități (curent/apă/…)',
        'waste_disposal' => 'Eliminarea deșeurilor',
        'other' => 'Altele',
    ],

    'status' => [
        'active' => 'Activ',
        'terminated' => 'Reziliat',
        'expired' => 'Expirat',
    ],

    'billing_cycle' => [
        'monthly' => 'Lunar',
        'quarterly' => 'Trimestrial',
        'yearly' => 'Anual',
        'one_time' => 'Unic',
    ],

    'reminder' => [
        'title' => 'Perioada de preaviz se apropie: :name',
        'body' => 'Contract cu :partner — rezilierea este posibilă în termen până la :deadline (sfârșitul contractului :end). Verificați acum și decideți.',
    ],

    'stats' => [
        'active' => 'Contracte active',
        'needs_decision' => 'Data limită de reziliere se apropie',
        'yearly_costs' => 'Costuri pe an',
    ],

];
