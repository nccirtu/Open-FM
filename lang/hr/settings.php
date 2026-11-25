<?php

return [
    'title' => 'Postavke aplikacije',
    'saved' => 'Postavke spremljene',

    'sections' => [
        'general' => 'Općenito',
        'general_help' => 'Naziv aplikacije, npr. u naslovu preglednika i u e-mailovima.',
        'branding' => 'Logotip i izgled',
        'branding_help' => 'Logotipi i favicon vrijede za sve panele. Bez logotipa prikazuje se naziv panela kao tekst.',
        'mail' => 'Slanje e-maila',
        'mail_help' => 'Pošiljatelj za obavijesti i pozivnice. Ostavite prazno za korištenje konfiguracije poslužitelja.',
    ],

    'fields' => [
        'app_name' => 'Naziv aplikacije',
        'logo_light' => 'Logotip (svijetla tema)',
        'logo_light_help' => 'PNG ili SVG, maks. 2 MB.',
        'logo_dark' => 'Logotip (tamna tema)',
        'logo_dark_help' => 'Neobavezno. Bez vlastitog logotipa koristi se svijetli logotip.',
        'favicon' => 'Favicon',
        'logo_height' => 'Visina logotipa',
        'logo_height_help' => 'CSS vrijednost poput 2rem ili 40px.',
        'mail_from_name' => 'Naziv pošiljatelja',
        'mail_from_address' => 'Adresa pošiljatelja',
    ],
];
