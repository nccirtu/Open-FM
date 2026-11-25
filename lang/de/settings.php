<?php

return [
    'title' => 'App-Einstellungen',
    'saved' => 'Einstellungen gespeichert',

    'sections' => [
        'general' => 'Allgemein',
        'general_help' => 'Name der Anwendung, z. B. im Browser-Titel und in E-Mails.',
        'branding' => 'Logo & Erscheinungsbild',
        'branding_help' => 'Logos und Favicon gelten für alle Panels. Ohne Logo wird der Panel-Name als Text angezeigt.',
        'mail' => 'E-Mail-Versand',
        'mail_help' => 'Absender für Benachrichtigungen und Einladungen. Leer lassen, um die Server-Konfiguration zu verwenden.',
    ],

    'fields' => [
        'app_name' => 'Anwendungsname',
        'logo_light' => 'Logo (helles Design)',
        'logo_light_help' => 'PNG oder SVG, max. 2 MB.',
        'logo_dark' => 'Logo (dunkles Design)',
        'logo_dark_help' => 'Optional. Ohne eigenes Logo wird das helle Logo verwendet.',
        'favicon' => 'Favicon',
        'logo_height' => 'Logo-Höhe',
        'logo_height_help' => 'CSS-Wert wie 2rem oder 40px.',
        'mail_from_name' => 'Absendername',
        'mail_from_address' => 'Absenderadresse',
    ],
];
