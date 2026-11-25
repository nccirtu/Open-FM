<?php

/**
 * Overrides only the plugin wording that says "media" — the product calls it document management.
 */
return [
    'title' => 'Document management',
    'navigation' => [
        'group' => 'Content',
        'label' => 'Document management',
    ],
    'actions' => [
        'browse_media' => 'Browse documents',
    ],
    'messages' => [
        'library' => 'Document library',
        'regenerate_for_all_description' => 'This will regenerate conversions for all documents in the library. This may take some time.',
        'regeneration_started' => 'Regeneration started successfully.',
        'regeneration_error' => 'Error during regeneration: :message',
        'replace_file_help' => 'Upload a new file to replace the current one while keeping the same document record.',
    ],
];
