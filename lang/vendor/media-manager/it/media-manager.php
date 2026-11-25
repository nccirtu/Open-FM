<?php

/**
 * Sovrascrive solo i termini del plugin che dicono „media“ — nel prodotto si chiama gestione documenti.
 */
return [
    'title' => 'Gestione documenti',
    'navigation' => [
        'group' => 'Contenuti',
        'label' => 'Gestione documenti',
    ],
    'actions' => [
        'browse_media' => 'Sfoglia documenti',
    ],
    'messages' => [
        'library' => 'Libreria documenti',
        'regenerate_for_all_description' => 'Verranno rigenerate le conversioni di tutti i documenti della libreria. L\'operazione può richiedere del tempo.',
        'regeneration_started' => 'La rigenerazione è stata avviata correttamente.',
        'regeneration_error' => 'Errore durante la rigenerazione: :message',
        'replace_file_help' => 'Caricare un nuovo file per sostituire il file attuale mantenendo la stessa voce documento.',
    ],
];
