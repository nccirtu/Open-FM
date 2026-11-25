<?php

/**
 * Suprascrie doar termenii pluginului care spun „media” — în produs se numește gestiunea documentelor.
 */
return [
    'title' => 'Gestiunea documentelor',
    'navigation' => [
        'group' => 'Conținut',
        'label' => 'Gestiunea documentelor',
    ],
    'actions' => [
        'browse_media' => 'Răsfoiește documentele',
    ],
    'messages' => [
        'library' => 'Biblioteca de documente',
        'regenerate_for_all_description' => 'Aceasta va regenera conversiile pentru toate documentele din bibliotecă. Poate dura ceva timp.',
        'regeneration_started' => 'Regenerarea a fost pornită cu succes.',
        'regeneration_error' => 'Eroare la regenerare: :message',
        'replace_file_help' => 'Încărcați un fișier nou pentru a înlocui fișierul curent și a păstra aceeași înregistrare de document.',
    ],
];
