<?php

return [
    'title' => 'Impostazioni app',
    'saved' => 'Impostazioni salvate',

    'sections' => [
        'general' => 'Generale',
        'general_help' => 'Nome dell\'applicazione, ad es. nel titolo del browser e nelle e-mail.',
        'branding' => 'Logo e aspetto',
        'branding_help' => 'Loghi e favicon valgono per tutti i pannelli. Senza logo viene mostrato il nome del pannello come testo.',
        'mail' => 'Invio e-mail',
        'mail_help' => 'Mittente per notifiche e inviti. Lasciare vuoto per usare la configurazione del server.',
    ],

    'fields' => [
        'app_name' => 'Nome applicazione',
        'logo_light' => 'Logo (tema chiaro)',
        'logo_light_help' => 'PNG o SVG, max 2 MB.',
        'logo_dark' => 'Logo (tema scuro)',
        'logo_dark_help' => 'Facoltativo. Senza logo dedicato viene usato il logo chiaro.',
        'favicon' => 'Favicon',
        'logo_height' => 'Altezza logo',
        'logo_height_help' => 'Valore CSS come 2rem o 40px.',
        'mail_from_name' => 'Nome mittente',
        'mail_from_address' => 'Indirizzo mittente',
    ],
];
