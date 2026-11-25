<?php

return [
    'title' => 'App settings',
    'saved' => 'Settings saved',

    'sections' => [
        'general' => 'General',
        'general_help' => 'Name of the application, e.g. in the browser title and in emails.',
        'branding' => 'Logo & appearance',
        'branding_help' => 'Logos and favicon apply to all panels. Without a logo the panel name is shown as text.',
        'mail' => 'Email sending',
        'mail_help' => 'Sender for notifications and invitations. Leave empty to use the server configuration.',
    ],

    'fields' => [
        'app_name' => 'Application name',
        'logo_light' => 'Logo (light theme)',
        'logo_light_help' => 'PNG or SVG, max. 2 MB.',
        'logo_dark' => 'Logo (dark theme)',
        'logo_dark_help' => 'Optional. Without its own logo the light logo is used.',
        'favicon' => 'Favicon',
        'logo_height' => 'Logo height',
        'logo_height_help' => 'CSS value such as 2rem or 40px.',
        'mail_from_name' => 'Sender name',
        'mail_from_address' => 'Sender address',
    ],
];
