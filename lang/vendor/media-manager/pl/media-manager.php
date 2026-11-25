<?php

/**
 * Nadpisuje tylko te terminy wtyczki, które mówią „media” — w produkcie nazywa się to zarządzaniem dokumentami.
 */
return [
    'title' => 'Zarządzanie dokumentami',
    'navigation' => [
        'group' => 'Treść',
        'label' => 'Zarządzanie dokumentami',
    ],
    'actions' => [
        'browse_media' => 'Przeglądaj dokumenty',
    ],
    'messages' => [
        'library' => 'Biblioteka dokumentów',
        'regenerate_for_all_description' => 'Spowoduje to ponowne wygenerowanie konwersji dla wszystkich dokumentów w bibliotece. Może to chwilę potrwać.',
        'regeneration_started' => 'Ponowne generowanie zostało pomyślnie uruchomione.',
        'regeneration_error' => 'Błąd podczas ponownego generowania: :message',
        'replace_file_help' => 'Prześlij nowy plik, aby zastąpić bieżący plik i zachować ten sam wpis dokumentu.',
    ],
];
