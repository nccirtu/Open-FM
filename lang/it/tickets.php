<?php

return [

    'model' => 'Guasto',
    'models' => 'Guasti',
    'navigation' => 'Guasti',

    'fields' => [
        'ticket_number' => 'N. guasto',
        'property' => 'Immobile',
        'asset' => 'Asset interessato',
        'type' => 'Tipo',
        'category' => 'Categoria',
        'priority' => 'Priorità',
        'status' => 'Stato',
        'title' => 'Titolo',
        'description' => 'Descrizione',
        'photos' => 'Foto',
        'reporter' => 'Segnalato da',
        'assignee' => 'Assegnato a',
        'company' => 'Fornitore di servizi',
        'due_at' => 'Scadenza il',
        'resolved_at' => 'Risolto il',
        'resolution_notes' => 'Nota di risoluzione',
        'created_at' => 'Segnalato il',
        'suspension_reason' => 'Motivo dell\'attesa',
        'estimate_status' => 'Preventivo',
        'estimate_amount' => 'Importo offerto',
        'sla' => 'Scadenza SLA',
    ],

    'sections' => [
        'report' => 'Segnalazione',
        'processing' => 'Lavorazione',
    ],

    'type' => [
        'fault' => 'Guasto',
        'defect' => 'Difetto',
        'request' => 'Richiesta',
    ],

    'category' => [
        'electrical' => 'Impianti elettrici',
        'plumbing' => 'Impianti idrosanitari',
        'hvac' => 'Riscaldamento / ventilazione / climatizzazione',
        'refrigeration' => 'Refrigerazione',
        'building' => 'Edificio / opere edili',
        'safety' => 'Sicurezza / antincendio',
        'cleaning' => 'Pulizie',
        'other' => 'Altro',
    ],

    'priority' => [
        'p1' => 'P1 — Emergenza',
        'p2' => 'P2 — Urgente',
        'p3' => 'P3 — Standard',
    ],

    'status' => [
        'reported' => 'Segnalato',
        'accepted' => 'Preso in carico',
        'in_progress' => 'In lavorazione',
        'waiting' => 'In attesa',
        'resolved' => 'Risolto',
        'closed' => 'Chiuso',
    ],

    'suspension_reason' => [
        'awaiting_parts' => 'In attesa di ricambi',
        'awaiting_partner' => 'In attesa del fornitore di servizi',
        'other' => 'Altro',
    ],

    'estimate_status' => [
        'requested' => 'Richiesto',
        'received' => 'Ricevuto',
        'approved' => 'Approvato',
        'rejected' => 'Rifiutato',
    ],

    'sla' => [
        'model' => 'Regola SLA',
        'models' => 'Regole SLA',
        'navigation' => 'Regole SLA',
        'response_hours' => 'Tempo di reazione (ore)',
        'response_hours_help' => 'Termine entro cui deve arrivare la prima risposta di un incaricato.',
        'resolution_hours' => 'Tempo di risoluzione (ore)',
        'resolution_hours_help' => 'Termine per la risoluzione; lo stato “In attesa” mette in pausa il conteggio.',
        'priority_unique' => 'Per questa priorità esiste già una regola SLA.',
        'escalation' => [
            'title' => 'SLA violato',
            'body' => 'Il guasto ":title" ha superato la scadenza SLA (termine di risoluzione: :due).',
        ],
    ],

    'filters' => [
        'open' => 'Solo aperti',
    ],

    'new' => 'NUOVO',

    'reporter_stores' => 'Immobili (assegnazione segnalatori)',
    'reporter_stores_help' => 'Nel portale servizi i segnalatori vedono solo questi immobili; senza assegnazione tutti gli immobili del loro tenant.',

    'service_partner_help' => 'Per accessi dei fornitori di servizi: gli utenti con ruolo “Fornitore di servizi” vedono solo i guasti e gli ordini di manutenzione di questa azienda.',

    'replies' => [
        'title' => 'Cronologia e risposte',
        'message' => 'Messaggio',
        'attachments' => 'Allegati',
        'internal_note' => 'Nota interna',
        'internal_note_help' => 'Le note interne non sono visibili ai segnalatori nel portale servizi.',
        'author' => 'Da',
        'created_at' => 'Data',
        'create' => 'Aggiungi risposta',
    ],

    'activities' => [
        'title' => 'Attività',
    ],

    'timeline' => [
        'automation' => 'L\'automazione ":rule" è stata eseguita.',
    ],

    'actions' => [
        'label' => 'Azioni',
    ],

    'process' => [
        'action' => 'Gestisci guasto',
        'heading' => 'Gestisci guasto',
        'submit' => 'Salva',
    ],

    'stats' => [
        'open' => 'Guasti aperti',
        'p1_open' => 'Emergenze aperte (P1)',
        'overdue' => 'Scaduti',
        'resolved_30d' => 'Risolti (30 giorni)',
    ],

    'automations' => [
        'model' => 'Regola di automazione',
        'models' => 'Regole di automazione',
        'navigation' => 'Automazioni',
        'name' => 'Nome',
        'is_active' => 'Attiva',
        'event' => 'Evento',
        'events' => [
            'created' => 'Ticket segnalato',
            'status_changed' => 'Stato modificato',
            'assigned' => 'Ticket assegnato',
            'reply_added' => 'Risposta aggiunta',
        ],
        'conditions' => 'Condizioni (devono valere tutte)',
        'condition_field' => 'Campo',
        'condition_value' => 'Valore',
        'condition_fields' => [
            'category' => 'Categoria',
            'priority' => 'Priorità',
            'type' => 'Tipo',
            'status' => 'Stato',
        ],
        'actions' => 'Azioni',
        'action_type' => 'Azione',
        'action_value' => 'Valore',
        'action_note' => 'Testo della nota',
        'action_types' => [
            'assign_to' => 'Assegna il ticket a',
            'set_status' => 'Imposta stato',
            'set_priority' => 'Imposta priorità',
            'add_internal_note' => 'Aggiungi nota interna',
        ],
    ],

    'portal' => [
        'navigation' => 'Le mie segnalazioni',
        'report' => 'Segnala guasto',
        'report_for_asset' => 'Segnala guasto',
        'confirm' => [
            'action' => 'Conferma risoluzione',
            'heading' => 'Conferma risoluzione',
            'submit' => 'Conferma e chiudi',
            'rating' => 'Soddisfazione',
            'rating_help' => 'Quanto è soddisfatto della risoluzione? (facoltativo)',
        ],
        'reopen' => [
            'action' => 'Riapri',
            'heading' => 'Riapri segnalazione',
            'submit' => 'Riapri',
            'reason' => 'Cosa non è ancora a posto?',
            'reply_prefix' => 'Segnalazione riaperta:',
        ],
    ],

    'notifications' => [
        'assigned' => [
            'title' => 'Guasto assegnato',
            'body' => 'Le è stato assegnato il guasto ":title" (:property).',
        ],
        'reply' => [
            'title' => 'Nuova risposta',
            'body' => ':author ha risposto al guasto ":title".',
        ],
        'status' => [
            'title' => 'Stato della sua segnalazione modificato',
            'body' => 'La sua segnalazione ":title" ora ha lo stato “:status”.',
        ],
    ],

];
