<?php

return [

    'tenant' => [
        'model' => null,
        'title_attribute' => 'name',
    ],

    'entities' => ['asset', 'property', 'ticket', 'contract', 'project'],

    'relationship' => 'customFieldValues',

];
