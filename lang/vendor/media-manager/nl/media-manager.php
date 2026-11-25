<?php

/**
 * Overschrijft alleen de termen van de plugin die "media" zeggen — in het product heet het documentbeheer.
 */
return [
    'title' => 'Documentbeheer',
    'navigation' => [
        'group' => 'Inhoud',
        'label' => 'Documentbeheer',
    ],
    'actions' => [
        'browse_media' => 'Documenten doorzoeken',
    ],
    'messages' => [
        'library' => 'Documentbibliotheek',
        'regenerate_for_all_description' => 'Hiermee worden de conversies voor alle documenten in de bibliotheek opnieuw gegenereerd. Dit kan enige tijd duren.',
        'regeneration_started' => 'Het opnieuw genereren is succesvol gestart.',
        'regeneration_error' => 'Fout bij het opnieuw genereren: :message',
        'replace_file_help' => 'Upload een nieuw bestand om het huidige bestand te vervangen en dezelfde documentvermelding te behouden.',
    ],
];
