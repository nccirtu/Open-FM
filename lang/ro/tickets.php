<?php

return [

    'model' => 'Defecțiune',
    'models' => 'Defecțiuni',
    'navigation' => 'Defecțiuni',

    'fields' => [
        'ticket_number' => 'Nr. defecțiune',
        'property' => 'Obiect',
        'asset' => 'Asset afectat',
        'type' => 'Tip',
        'category' => 'Categorie',
        'priority' => 'Prioritate',
        'status' => 'Stare',
        'title' => 'Titlu',
        'description' => 'Descriere',
        'photos' => 'Fotografii',
        'reporter' => 'Raportat de',
        'assignee' => 'Atribuit lui',
        'company' => 'Prestator',
        'due_at' => 'Scadent la',
        'resolved_at' => 'Rezolvat la',
        'resolution_notes' => 'Notă de rezolvare',
        'created_at' => 'Raportat la',
        'suspension_reason' => 'Motivul așteptării',
        'estimate_status' => 'Deviz estimativ',
        'estimate_amount' => 'Valoarea ofertei',
        'sla' => 'Termen SLA',
    ],

    'sections' => [
        'report' => 'Sesizare',
        'processing' => 'Procesare',
    ],

    'type' => [
        'fault' => 'Defecțiune',
        'defect' => 'Deficiență',
        'request' => 'Solicitare',
    ],

    'category' => [
        'electrical' => 'Electric',
        'plumbing' => 'Sanitar',
        'hvac' => 'Încălzire / Ventilație / Climatizare',
        'refrigeration' => 'Tehnică frigorifică',
        'building' => 'Clădire / Construcție',
        'safety' => 'Siguranță / Protecție la incendiu',
        'cleaning' => 'Curățenie',
        'other' => 'Altele',
    ],

    'priority' => [
        'p1' => 'P1 — Urgență',
        'p2' => 'P2 — Urgent',
        'p3' => 'P3 — Standard',
    ],

    'status' => [
        'reported' => 'Raportată',
        'accepted' => 'Acceptată',
        'in_progress' => 'În procesare',
        'waiting' => 'În așteptare',
        'resolved' => 'Rezolvată',
        'closed' => 'Închisă',
    ],

    'suspension_reason' => [
        'awaiting_parts' => 'Așteaptă piese de schimb',
        'awaiting_partner' => 'Așteaptă prestatorul',
        'other' => 'Altele',
    ],

    'estimate_status' => [
        'requested' => 'Solicitat',
        'received' => 'Primit',
        'approved' => 'Aprobat',
        'rejected' => 'Respins',
    ],

    'sla' => [
        'model' => 'Regulă SLA',
        'models' => 'Reguli SLA',
        'navigation' => 'Reguli SLA',
        'response_hours' => 'Timp de reacție (ore)',
        'response_hours_help' => 'Termenul până la primul răspuns al unui operator.',
        'resolution_hours' => 'Timp de rezolvare (ore)',
        'resolution_hours_help' => 'Termenul până la rezolvare; starea „În așteptare" oprește cronometrul.',
        'priority_unique' => 'Pentru această prioritate există deja o regulă SLA.',
        'escalation' => [
            'title' => 'SLA încălcat',
            'body' => 'Defecțiunea ":title" și-a depășit termenul SLA (termen de rezolvare: :due).',
        ],
    ],

    'filters' => [
        'open' => 'Doar deschise',
    ],

    'new' => 'NOU',

    'reporter_stores' => 'Obiecte (atribuire raportori)',
    'reporter_stores_help' => 'Raportorii văd în portalul de servicii doar aceste obiecte; fără atribuire, toate obiectele organizației lor.',

    'service_partner_help' => 'Pentru accesele prestatorilor: utilizatorii cu rolul „Prestator" văd doar defecțiunile și comenzile de mentenanță ale acestei firme.',

    'replies' => [
        'title' => 'Istoric & Răspunsuri',
        'message' => 'Mesaj',
        'attachments' => 'Atașamente',
        'internal_note' => 'Notiță internă',
        'internal_note_help' => 'Notițele interne nu sunt vizibile raportorilor în portalul de servicii.',
        'author' => 'De la',
        'created_at' => 'Dată',
        'create' => 'Adaugă răspuns',
    ],

    'activities' => [
        'title' => 'Activități',
    ],

    'timeline' => [
        'automation' => 'Automatizarea ":rule" a fost executată.',
    ],

    'actions' => [
        'label' => 'Acțiuni',
    ],

    'process' => [
        'action' => 'Procesează defecțiunea',
        'heading' => 'Procesează defecțiunea',
        'submit' => 'Salvează',
    ],

    'stats' => [
        'open' => 'Defecțiuni deschise',
        'p1_open' => 'Urgențe deschise (P1)',
        'overdue' => 'Restante',
        'resolved_30d' => 'Rezolvate (30 de zile)',
    ],

    'automations' => [
        'model' => 'Regulă de automatizare',
        'models' => 'Reguli de automatizare',
        'navigation' => 'Automatizări',
        'name' => 'Nume',
        'is_active' => 'Activă',
        'event' => 'Eveniment',
        'events' => [
            'created' => 'Tichet raportat',
            'status_changed' => 'Stare modificată',
            'assigned' => 'Tichet atribuit',
            'reply_added' => 'Răspuns adăugat',
        ],
        'conditions' => 'Condiții (toate trebuie îndeplinite)',
        'condition_field' => 'Câmp',
        'condition_value' => 'Valoare',
        'condition_fields' => [
            'category' => 'Categorie',
            'priority' => 'Prioritate',
            'type' => 'Tip',
            'status' => 'Stare',
        ],
        'actions' => 'Acțiuni',
        'action_type' => 'Acțiune',
        'action_value' => 'Valoare',
        'action_note' => 'Textul notiței',
        'action_types' => [
            'assign_to' => 'Atribuie tichetul lui',
            'set_status' => 'Setează starea',
            'set_priority' => 'Setează prioritatea',
            'add_internal_note' => 'Adaugă notiță internă',
        ],
    ],

    'portal' => [
        'navigation' => 'Sesizările mele',
        'report' => 'Raportează o defecțiune',
        'report_for_asset' => 'Raportează o defecțiune',
        'confirm' => [
            'action' => 'Confirmă rezolvarea',
            'heading' => 'Confirmă rezolvarea',
            'submit' => 'Confirmă & închide',
            'rating' => 'Satisfacție',
            'rating_help' => 'Cât de mulțumit sunteți de rezolvare? (opțional)',
        ],
        'reopen' => [
            'action' => 'Redeschide',
            'heading' => 'Redeschide sesizarea',
            'submit' => 'Redeschide',
            'reason' => 'Ce nu este încă în ordine?',
            'reply_prefix' => 'Sesizare redeschisă:',
        ],
    ],

    'notifications' => [
        'assigned' => [
            'title' => 'Defecțiune atribuită',
            'body' => 'Vi s-a atribuit defecțiunea ":title" (:property).',
        ],
        'reply' => [
            'title' => 'Răspuns nou',
            'body' => ':author a răspuns la defecțiunea ":title".',
        ],
        'status' => [
            'title' => 'Starea sesizării dvs. s-a modificat',
            'body' => 'Sesizarea dvs. ":title" are acum starea „:status".',
        ],
    ],

];
