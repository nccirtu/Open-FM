<?php

/**
 * Überschreibt nur die Begriffe des Plugins, die „Medien“ sagen — im Produkt heißt es Dokumentenverwaltung.
 */
return [
    'title' => 'Dokumentenverwaltung',
    'navigation' => [
        'group' => 'Inhalt',
        'label' => 'Dokumentenverwaltung',
    ],
    'actions' => [
        'browse_media' => 'Dokumente durchsuchen',
    ],
    'messages' => [
        'library' => 'Dokumentenbibliothek',
        'regenerate_for_all_description' => 'Dadurch werden die Konvertierungen für alle Dokumente in der Bibliothek neu generiert. Dies kann einige Zeit dauern.',
        'regeneration_started' => 'Die Neugenerierung wurde erfolgreich gestartet.',
        'regeneration_error' => 'Fehler bei der Neugenerierung: :message',
        'replace_file_help' => 'Laden Sie eine neue Datei hoch, um die aktuelle Datei zu ersetzen und denselben Dokumenteintrag beizubehalten.',
    ],
];
