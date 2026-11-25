<?php

return [
    'title' => 'Setările aplicației',
    'saved' => 'Setări salvate',

    'sections' => [
        'general' => 'General',
        'general_help' => 'Numele aplicației, de ex. în titlul browserului și în e-mailuri.',
        'branding' => 'Logo & Aspect',
        'branding_help' => 'Logourile și faviconul se aplică tuturor panourilor. Fără logo, numele panoului se afișează ca text.',
        'mail' => 'Trimitere e-mail',
        'mail_help' => 'Expeditorul pentru notificări și invitații. Lăsați gol pentru a folosi configurația serverului.',
    ],

    'fields' => [
        'app_name' => 'Numele aplicației',
        'logo_light' => 'Logo (temă deschisă)',
        'logo_light_help' => 'PNG sau SVG, max. 2 MB.',
        'logo_dark' => 'Logo (temă întunecată)',
        'logo_dark_help' => 'Opțional. Fără un logo propriu se folosește logoul temei deschise.',
        'favicon' => 'Favicon',
        'logo_height' => 'Înălțimea logoului',
        'logo_height_help' => 'Valoare CSS precum 2rem sau 40px.',
        'mail_from_name' => 'Nume expeditor',
        'mail_from_address' => 'Adresă expeditor',
    ],
];
