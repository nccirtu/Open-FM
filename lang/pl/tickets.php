<?php

return [

    'model' => 'Usterka',
    'models' => 'Usterki',
    'navigation' => 'Usterki',

    'fields' => [
        'ticket_number' => 'Nr usterki',
        'property' => 'Obiekt',
        'asset' => 'Dotknięty asset',
        'type' => 'Rodzaj',
        'category' => 'Kategoria',
        'priority' => 'Priorytet',
        'status' => 'Status',
        'title' => 'Tytuł',
        'description' => 'Opis',
        'photos' => 'Zdjęcia',
        'reporter' => 'Zgłoszone przez',
        'assignee' => 'Przypisane do',
        'company' => 'Usługodawca',
        'due_at' => 'Termin',
        'resolved_at' => 'Rozwiązano',
        'resolution_notes' => 'Notatka o rozwiązaniu',
        'created_at' => 'Zgłoszono',
        'suspension_reason' => 'Powód oczekiwania',
        'estimate_status' => 'Kosztorys',
        'estimate_amount' => 'Kwota oferty',
        'sla' => 'Termin SLA',
    ],

    'sections' => [
        'report' => 'Zgłoszenie',
        'processing' => 'Realizacja',
    ],

    'type' => [
        'fault' => 'Awaria',
        'defect' => 'Wada',
        'request' => 'Zapytanie',
    ],

    'category' => [
        'electrical' => 'Elektryka',
        'plumbing' => 'Instalacje sanitarne',
        'hvac' => 'Ogrzewanie / wentylacja / klimatyzacja',
        'refrigeration' => 'Chłodnictwo',
        'building' => 'Budynek / budowlane',
        'safety' => 'Bezpieczeństwo / ochrona przeciwpożarowa',
        'cleaning' => 'Sprzątanie',
        'other' => 'Inne',
    ],

    'priority' => [
        'p1' => 'P1 — awaria krytyczna',
        'p2' => 'P2 — pilne',
        'p3' => 'P3 — standard',
    ],

    'status' => [
        'reported' => 'Zgłoszona',
        'accepted' => 'Przyjęta',
        'in_progress' => 'W realizacji',
        'waiting' => 'Oczekuje',
        'resolved' => 'Rozwiązana',
        'closed' => 'Zamknięta',
    ],

    'suspension_reason' => [
        'awaiting_parts' => 'Oczekuje na części zamienne',
        'awaiting_partner' => 'Oczekuje na usługodawcę',
        'other' => 'Inne',
    ],

    'estimate_status' => [
        'requested' => 'Zamówiony',
        'received' => 'Otrzymany',
        'approved' => 'Zatwierdzony',
        'rejected' => 'Odrzucony',
    ],

    'sla' => [
        'model' => 'Reguła SLA',
        'models' => 'Reguły SLA',
        'navigation' => 'Reguły SLA',
        'response_hours' => 'Czas reakcji (godziny)',
        'response_hours_help' => 'Termin na pierwszą odpowiedź osoby obsługującej.',
        'resolution_hours' => 'Czas rozwiązania (godziny)',
        'resolution_hours_help' => 'Termin na rozwiązanie; status „Oczekuje" wstrzymuje zegar.',
        'priority_unique' => 'Dla tego priorytetu istnieje już reguła SLA.',
        'escalation' => [
            'title' => 'Naruszono SLA',
            'body' => 'Usterka ":title" przekroczyła swój termin SLA (termin rozwiązania: :due).',
        ],
    ],

    'filters' => [
        'open' => 'Tylko otwarte',
    ],

    'new' => 'NOWA',

    'reporter_stores' => 'Obiekty (przypisanie zgłaszającego)',
    'reporter_stores_help' => 'Zgłaszający widzą w portalu serwisowym tylko te obiekty; bez przypisania — wszystkie obiekty swojej organizacji.',

    'service_partner_help' => 'Dla dostępów usługodawców: użytkownicy z rolą „Usługodawca" widzą tylko usterki i zlecenia konserwacji tej firmy.',

    'replies' => [
        'title' => 'Historia i odpowiedzi',
        'message' => 'Wiadomość',
        'attachments' => 'Załączniki',
        'internal_note' => 'Notatka wewnętrzna',
        'internal_note_help' => 'Notatki wewnętrzne nie są widoczne dla zgłaszających w portalu serwisowym.',
        'author' => 'Od',
        'created_at' => 'Data',
        'create' => 'Dodaj odpowiedź',
    ],

    'activities' => [
        'title' => 'Aktywności',
    ],

    'timeline' => [
        'automation' => 'Wykonano automatyzację ":rule".',
    ],

    'actions' => [
        'label' => 'Akcje',
    ],

    'process' => [
        'action' => 'Obsłuż usterkę',
        'heading' => 'Obsłuż usterkę',
        'submit' => 'Zapisz',
    ],

    'stats' => [
        'open' => 'Otwarte usterki',
        'p1_open' => 'Otwarte awarie krytyczne (P1)',
        'overdue' => 'Zaległe',
        'resolved_30d' => 'Rozwiązane (30 dni)',
    ],

    'automations' => [
        'model' => 'Reguła automatyzacji',
        'models' => 'Reguły automatyzacji',
        'navigation' => 'Automatyzacje',
        'name' => 'Nazwa',
        'is_active' => 'Aktywna',
        'event' => 'Zdarzenie',
        'events' => [
            'created' => 'Zgłoszono usterkę',
            'status_changed' => 'Zmieniono status',
            'assigned' => 'Przypisano usterkę',
            'reply_added' => 'Dodano odpowiedź',
        ],
        'conditions' => 'Warunki (wszystkie muszą być spełnione)',
        'condition_field' => 'Pole',
        'condition_value' => 'Wartość',
        'condition_fields' => [
            'category' => 'Kategoria',
            'priority' => 'Priorytet',
            'type' => 'Rodzaj',
            'status' => 'Status',
        ],
        'actions' => 'Akcje',
        'action_type' => 'Akcja',
        'action_value' => 'Wartość',
        'action_note' => 'Treść notatki',
        'action_types' => [
            'assign_to' => 'Przypisz usterkę do',
            'set_status' => 'Ustaw status',
            'set_priority' => 'Ustaw priorytet',
            'add_internal_note' => 'Dodaj notatkę wewnętrzną',
        ],
    ],

    'portal' => [
        'navigation' => 'Moje zgłoszenia',
        'report' => 'Zgłoś usterkę',
        'report_for_asset' => 'Zgłoś usterkę',
        'confirm' => [
            'action' => 'Potwierdź wykonanie',
            'heading' => 'Potwierdź wykonanie',
            'submit' => 'Potwierdź i zamknij',
            'rating' => 'Zadowolenie',
            'rating_help' => 'Jak bardzo jesteś zadowolony z realizacji? (opcjonalnie)',
        ],
        'reopen' => [
            'action' => 'Otwórz ponownie',
            'heading' => 'Otwórz zgłoszenie ponownie',
            'submit' => 'Otwórz ponownie',
            'reason' => 'Co jest jeszcze nie w porządku?',
            'reply_prefix' => 'Zgłoszenie otwarte ponownie:',
        ],
    ],

    'notifications' => [
        'assigned' => [
            'title' => 'Przypisano usterkę',
            'body' => 'Przypisano Ci usterkę ":title" (:property).',
        ],
        'reply' => [
            'title' => 'Nowa odpowiedź',
            'body' => ':author odpowiedział(a) na usterkę ":title".',
        ],
        'status' => [
            'title' => 'Zmieniono status Twojego zgłoszenia',
            'body' => 'Twoje zgłoszenie ":title" ma teraz status „:status".',
        ],
    ],

];
