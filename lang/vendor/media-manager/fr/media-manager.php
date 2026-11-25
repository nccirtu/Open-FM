<?php

/**
 * Remplace uniquement les termes du plugin qui disent « médias » — dans le produit, on parle de gestion documentaire.
 */
return [
    'title' => 'Gestion documentaire',
    'navigation' => [
        'group' => 'Contenu',
        'label' => 'Gestion documentaire',
    ],
    'actions' => [
        'browse_media' => 'Parcourir les documents',
    ],
    'messages' => [
        'library' => 'Bibliothèque documentaire',
        'regenerate_for_all_description' => 'Les conversions de tous les documents de la bibliothèque seront régénérées. Cela peut prendre un certain temps.',
        'regeneration_started' => 'La régénération a été lancée avec succès.',
        'regeneration_error' => 'Erreur lors de la régénération : :message',
        'replace_file_help' => 'Téléversez un nouveau fichier pour remplacer le fichier actuel tout en conservant la même entrée de document.',
    ],
];
