<?php

return [

    'model' => 'Kvar',
    'models' => 'Kvarovi',
    'navigation' => 'Kvarovi',

    'fields' => [
        'ticket_number' => 'Br. kvara',
        'property' => 'Objekt',
        'asset' => 'Pogođeno sredstvo',
        'type' => 'Vrsta',
        'category' => 'Kategorija',
        'priority' => 'Prioritet',
        'status' => 'Status',
        'title' => 'Naslov',
        'description' => 'Opis',
        'photos' => 'Fotografije',
        'reporter' => 'Prijavio/la',
        'assignee' => 'Dodijeljeno',
        'company' => 'Pružatelj usluga',
        'due_at' => 'Rok',
        'resolved_at' => 'Riješeno',
        'resolution_notes' => 'Napomena o rješenju',
        'created_at' => 'Prijavljeno',
        'suspension_reason' => 'Razlog čekanja',
        'estimate_status' => 'Predračun',
        'estimate_amount' => 'Iznos ponude',
        'sla' => 'SLA rok',
    ],

    'sections' => [
        'report' => 'Prijava',
        'processing' => 'Obrada',
    ],

    'type' => [
        'fault' => 'Kvar',
        'defect' => 'Nedostatak',
        'request' => 'Zahtjev',
    ],

    'category' => [
        'electrical' => 'Elektroinstalacije',
        'plumbing' => 'Vodoinstalacije',
        'hvac' => 'Grijanje / ventilacija / klima',
        'refrigeration' => 'Rashladna tehnika',
        'building' => 'Zgrada / građevinsko',
        'safety' => 'Sigurnost / zaštita od požara',
        'cleaning' => 'Čišćenje',
        'other' => 'Ostalo',
    ],

    'priority' => [
        'p1' => 'P1 — hitni slučaj',
        'p2' => 'P2 — hitno',
        'p3' => 'P3 — standardno',
    ],

    'status' => [
        'reported' => 'Prijavljen',
        'accepted' => 'Prihvaćen',
        'in_progress' => 'U obradi',
        'waiting' => 'Čeka',
        'resolved' => 'Riješen',
        'closed' => 'Zatvoren',
    ],

    'suspension_reason' => [
        'awaiting_parts' => 'Čeka rezervne dijelove',
        'awaiting_partner' => 'Čeka pružatelja usluga',
        'other' => 'Ostalo',
    ],

    'estimate_status' => [
        'requested' => 'Zatražen',
        'received' => 'Zaprimljen',
        'approved' => 'Odobren',
        'rejected' => 'Odbijen',
    ],

    'sla' => [
        'model' => 'SLA pravilo',
        'models' => 'SLA pravila',
        'navigation' => 'SLA pravila',
        'response_hours' => 'Vrijeme odziva (sati)',
        'response_hours_help' => 'Rok do prvog odgovora obrađivača.',
        'resolution_hours' => 'Vrijeme rješavanja (sati)',
        'resolution_hours_help' => 'Rok do rješenja; status „Čeka“ pauzira sat.',
        'priority_unique' => 'Za ovaj prioritet već postoji SLA pravilo.',
        'escalation' => [
            'title' => 'SLA prekršen',
            'body' => 'Kvar ":title" prekoračio je svoj SLA rok (rok za rješenje: :due).',
        ],
    ],

    'filters' => [
        'open' => 'Samo otvoreni',
    ],

    'new' => 'NOVO',

    'reporter_stores' => 'Objekti (dodjela prijavitelja)',
    'reporter_stores_help' => 'Prijavitelji u servisnom portalu vide samo ove objekte; bez dodjele vide sve objekte svog klijenta.',

    'service_partner_help' => 'Za pristupe pružatelja usluga: korisnici s ulogom „Pružatelj usluga“ vide samo kvarove i naloge za održavanje ove tvrtke.',

    'replies' => [
        'title' => 'Tijek i odgovori',
        'message' => 'Poruka',
        'attachments' => 'Privici',
        'internal_note' => 'Interna bilješka',
        'internal_note_help' => 'Interne bilješke nisu vidljive prijaviteljima u servisnom portalu.',
        'author' => 'Od',
        'created_at' => 'Datum',
        'create' => 'Dodaj odgovor',
    ],

    'activities' => [
        'title' => 'Aktivnosti',
    ],

    'timeline' => [
        'automation' => 'Automatizacija ":rule" je izvršena.',
    ],

    'actions' => [
        'label' => 'Radnje',
    ],

    'process' => [
        'action' => 'Obradi kvar',
        'heading' => 'Obradi kvar',
        'submit' => 'Spremi',
    ],

    'stats' => [
        'open' => 'Otvoreni kvarovi',
        'p1_open' => 'Otvoreni hitni slučajevi (P1)',
        'overdue' => 'U kašnjenju',
        'resolved_30d' => 'Riješeno (30 dana)',
    ],

    'automations' => [
        'model' => 'Pravilo automatizacije',
        'models' => 'Pravila automatizacije',
        'navigation' => 'Automatizacije',
        'name' => 'Naziv',
        'is_active' => 'Aktivno',
        'event' => 'Događaj',
        'events' => [
            'created' => 'Kvar prijavljen',
            'status_changed' => 'Status promijenjen',
            'assigned' => 'Kvar dodijeljen',
            'reply_added' => 'Odgovor dodan',
        ],
        'conditions' => 'Uvjeti (svi moraju biti ispunjeni)',
        'condition_field' => 'Polje',
        'condition_value' => 'Vrijednost',
        'condition_fields' => [
            'category' => 'Kategorija',
            'priority' => 'Prioritet',
            'type' => 'Vrsta',
            'status' => 'Status',
        ],
        'actions' => 'Radnje',
        'action_type' => 'Radnja',
        'action_value' => 'Vrijednost',
        'action_note' => 'Tekst bilješke',
        'action_types' => [
            'assign_to' => 'Dodijeli kvar korisniku',
            'set_status' => 'Postavi status',
            'set_priority' => 'Postavi prioritet',
            'add_internal_note' => 'Dodaj internu bilješku',
        ],
    ],

    'portal' => [
        'navigation' => 'Moje prijave',
        'report' => 'Prijavi kvar',
        'report_for_asset' => 'Prijavi kvar',
        'confirm' => [
            'action' => 'Potvrdi rješenje',
            'heading' => 'Potvrdi rješenje',
            'submit' => 'Potvrdi i zatvori',
            'rating' => 'Zadovoljstvo',
            'rating_help' => 'Koliko ste zadovoljni rješenjem? (neobavezno)',
        ],
        'reopen' => [
            'action' => 'Ponovno otvori',
            'heading' => 'Ponovno otvori prijavu',
            'submit' => 'Ponovno otvori',
            'reason' => 'Što još nije u redu?',
            'reply_prefix' => 'Prijava ponovno otvorena:',
        ],
    ],

    'notifications' => [
        'assigned' => [
            'title' => 'Kvar dodijeljen',
            'body' => 'Dodijeljen Vam je kvar ":title" (:property).',
        ],
        'reply' => [
            'title' => 'Novi odgovor',
            'body' => ':author je odgovorio/la na kvar ":title".',
        ],
        'status' => [
            'title' => 'Status Vaše prijave je promijenjen',
            'body' => 'Vaša prijava ":title" sada ima status „:status“.',
        ],
    ],

];
