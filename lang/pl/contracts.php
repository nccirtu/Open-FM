<?php

return [

    'model' => 'Umowa',
    'models' => 'Umowy',
    'navigation' => 'Umowy',

    'sections' => [
        'master_data' => 'Dane umowy',
        'term' => 'Okres obowiązywania i wypowiedzenie',
        'costs' => 'Koszty',
        'assignment' => 'Przypisanie i dokumenty',
    ],

    'fields' => [
        'name' => 'Nazwa',
        'contract_number' => 'Numer umowy',
        'partner_name' => 'Strona umowy',
        'type' => 'Rodzaj umowy',
        'status' => 'Status',
        'start_date' => 'Początek umowy',
        'end_date' => 'Koniec umowy',
        'end_date_help' => 'Pozostaw puste dla umów na czas nieokreślony.',
        'open_ended' => 'na czas nieokreślony',
        'notice_period_days' => 'Okres wypowiedzenia (dni)',
        'notice_period_days_help' => 'Termin przed końcem umowy, w którym należy złożyć wypowiedzenie.',
        'reminder_lead_days' => 'Wyprzedzenie przypomnienia (dni)',
        'reminder_lead_days_help' => 'Tyle dni przed terminem wypowiedzenia zostanie wysłane przypomnienie.',
        'notice_deadline' => 'Termin wypowiedzenia',
        'amount' => 'Kwota',
        'billing_cycle' => 'Okres rozliczeniowy',
        'properties' => 'Obiekty',
        'assets' => 'Assety',
        'notes' => 'Uwagi',
    ],

    'type' => [
        'maintenance' => 'Umowa konserwacyjna',
        'service' => 'Umowa o świadczenie usług',
        'rental' => 'Umowa najmu',
        'insurance' => 'Ubezpieczenie',
        'utility' => 'Media (prąd/woda/…)',
        'waste_disposal' => 'Odbiór odpadów',
        'other' => 'Inne',
    ],

    'status' => [
        'active' => 'Aktywna',
        'terminated' => 'Wypowiedziana',
        'expired' => 'Wygasła',
    ],

    'billing_cycle' => [
        'monthly' => 'Miesięcznie',
        'quarterly' => 'Kwartalnie',
        'yearly' => 'Rocznie',
        'one_time' => 'Jednorazowo',
    ],

    'reminder' => [
        'title' => 'Biegnie termin wypowiedzenia: :name',
        'body' => 'Umowa z :partner — wypowiedzenie możliwe w terminie do :deadline (koniec umowy :end). Sprawdź i podejmij decyzję.',
    ],

    'stats' => [
        'active' => 'Aktywne umowy',
        'needs_decision' => 'Zbliża się termin wypowiedzenia',
        'yearly_costs' => 'Koszty roczne',
    ],

];
