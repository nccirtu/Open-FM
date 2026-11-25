<?php

return [
    'title' => 'App-instellingen',
    'saved' => 'Instellingen opgeslagen',

    'sections' => [
        'general' => 'Algemeen',
        'general_help' => 'Naam van de applicatie, bijv. in de browsertitel en in e-mails.',
        'branding' => 'Logo & vormgeving',
        'branding_help' => 'Logo\'s en favicon gelden voor alle panels. Zonder logo wordt de panelnaam als tekst weergegeven.',
        'mail' => 'E-mailverzending',
        'mail_help' => 'Afzender voor meldingen en uitnodigingen. Leeg laten om de serverconfiguratie te gebruiken.',
    ],

    'fields' => [
        'app_name' => 'Applicatienaam',
        'logo_light' => 'Logo (licht thema)',
        'logo_light_help' => 'PNG of SVG, max. 2 MB.',
        'logo_dark' => 'Logo (donker thema)',
        'logo_dark_help' => 'Optioneel. Zonder eigen logo wordt het lichte logo gebruikt.',
        'favicon' => 'Favicon',
        'logo_height' => 'Logohoogte',
        'logo_height_help' => 'CSS-waarde zoals 2rem of 40px.',
        'mail_from_name' => 'Afzendernaam',
        'mail_from_address' => 'Afzenderadres',
    ],
];
