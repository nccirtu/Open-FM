<?php

return [

    'model' => 'Ugovor',
    'models' => 'Ugovori',
    'navigation' => 'Ugovori',

    'sections' => [
        'master_data' => 'Podaci o ugovoru',
        'term' => 'Trajanje i otkaz',
        'costs' => 'Troškovi',
        'assignment' => 'Dodjela i dokumentacija',
    ],

    'fields' => [
        'name' => 'Naziv',
        'contract_number' => 'Broj ugovora',
        'partner_name' => 'Ugovorni partner',
        'type' => 'Vrsta ugovora',
        'status' => 'Status',
        'start_date' => 'Početak ugovora',
        'end_date' => 'Kraj ugovora',
        'end_date_help' => 'Ostavite prazno kod ugovora na neodređeno vrijeme.',
        'open_ended' => 'na neodređeno',
        'notice_period_days' => 'Otkazni rok (dana)',
        'notice_period_days_help' => 'Rok prije isteka ugovora unutar kojeg se mora otkazati.',
        'reminder_lead_days' => 'Podsjetnik unaprijed (dana)',
        'reminder_lead_days_help' => 'Toliko dana prije krajnjeg roka za otkaz šalje se podsjetnik.',
        'notice_deadline' => 'Krajnji rok za otkaz',
        'amount' => 'Iznos',
        'billing_cycle' => 'Obračunsko razdoblje',
        'properties' => 'Objekti',
        'assets' => 'Sredstva',
        'notes' => 'Napomene',
    ],

    'type' => [
        'maintenance' => 'Ugovor o održavanju',
        'service' => 'Ugovor o uslugama',
        'rental' => 'Ugovor o najmu',
        'insurance' => 'Osiguranje',
        'utility' => 'Opskrba (struja/voda/…)',
        'waste_disposal' => 'Zbrinjavanje otpada',
        'other' => 'Ostalo',
    ],

    'status' => [
        'active' => 'Aktivan',
        'terminated' => 'Otkazan',
        'expired' => 'Istekao',
    ],

    'billing_cycle' => [
        'monthly' => 'Mjesečno',
        'quarterly' => 'Kvartalno',
        'yearly' => 'Godišnje',
        'one_time' => 'Jednokratno',
    ],

    'reminder' => [
        'title' => 'Otkazni rok istječe: :name',
        'body' => 'Ugovor s partnerom :partner — otkaz je pravodobno moguć do :deadline (kraj ugovora :end). Provjerite i odlučite.',
    ],

    'stats' => [
        'active' => 'Aktivni ugovori',
        'needs_decision' => 'Približava se krajnji rok za otkaz',
        'yearly_costs' => 'Troškovi godišnje',
    ],

];
