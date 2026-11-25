<?php

/**
 * Nadjačava samo pojmove plugina koji kažu „mediji“ — u proizvodu se to zove upravljanje dokumentima.
 */
return [
    'title' => 'Upravljanje dokumentima',
    'navigation' => [
        'group' => 'Sadržaj',
        'label' => 'Upravljanje dokumentima',
    ],
    'actions' => [
        'browse_media' => 'Pregledaj dokumente',
    ],
    'messages' => [
        'library' => 'Biblioteka dokumenata',
        'regenerate_for_all_description' => 'Time se ponovno generiraju konverzije za sve dokumente u biblioteci. To može potrajati neko vrijeme.',
        'regeneration_started' => 'Ponovno generiranje uspješno je pokrenuto.',
        'regeneration_error' => 'Pogreška pri ponovnom generiranju: :message',
        'replace_file_help' => 'Prenesite novu datoteku kako biste zamijenili trenutnu datoteku i zadržali isti zapis dokumenta.',
    ],
];
