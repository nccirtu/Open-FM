<?php

return [
    'title' => 'Paramètres de l\'application',
    'saved' => 'Paramètres enregistrés',

    'sections' => [
        'general' => 'Général',
        'general_help' => 'Nom de l\'application, affiché p. ex. dans le titre du navigateur et dans les e-mails.',
        'branding' => 'Logo & apparence',
        'branding_help' => 'Les logos et le favicon s\'appliquent à tous les panels. Sans logo, le nom du panel est affiché en texte.',
        'mail' => 'Envoi d\'e-mails',
        'mail_help' => 'Expéditeur des notifications et invitations. Laisser vide pour utiliser la configuration du serveur.',
    ],

    'fields' => [
        'app_name' => 'Nom de l\'application',
        'logo_light' => 'Logo (thème clair)',
        'logo_light_help' => 'PNG ou SVG, max. 2 Mo.',
        'logo_dark' => 'Logo (thème sombre)',
        'logo_dark_help' => 'Optionnel. Sans logo dédié, le logo clair est utilisé.',
        'favicon' => 'Favicon',
        'logo_height' => 'Hauteur du logo',
        'logo_height_help' => 'Valeur CSS comme 2rem ou 40px.',
        'mail_from_name' => 'Nom de l\'expéditeur',
        'mail_from_address' => 'Adresse de l\'expéditeur',
    ],
];
