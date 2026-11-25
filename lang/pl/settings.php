<?php

return [
    'title' => 'Ustawienia aplikacji',
    'saved' => 'Ustawienia zapisane',

    'sections' => [
        'general' => 'Ogólne',
        'general_help' => 'Nazwa aplikacji, np. w tytule przeglądarki i w e-mailach.',
        'branding' => 'Logo i wygląd',
        'branding_help' => 'Logo i favicon obowiązują we wszystkich panelach. Bez logo wyświetlana jest nazwa panelu jako tekst.',
        'mail' => 'Wysyłka e-mail',
        'mail_help' => 'Nadawca powiadomień i zaproszeń. Pozostaw puste, aby użyć konfiguracji serwera.',
    ],

    'fields' => [
        'app_name' => 'Nazwa aplikacji',
        'logo_light' => 'Logo (jasny motyw)',
        'logo_light_help' => 'PNG lub SVG, maks. 2 MB.',
        'logo_dark' => 'Logo (ciemny motyw)',
        'logo_dark_help' => 'Opcjonalnie. Bez własnego logo używane jest logo jasne.',
        'favicon' => 'Favicon',
        'logo_height' => 'Wysokość logo',
        'logo_height_help' => 'Wartość CSS, np. 2rem lub 40px.',
        'mail_from_name' => 'Nazwa nadawcy',
        'mail_from_address' => 'Adres nadawcy',
    ],
];
