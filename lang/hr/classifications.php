<?php

return [
    'import' => [
        'label' => 'Predložak klasifikacije',
        'help' => 'Dodjeljuje kategorije sredstava i mjesta troška iz fiksne sheme (npr. DIN 276) i izrađuje odgovarajuća dodatna polja po tipu sredstva (npr. rokove ispitivanja za vatrogasne aparate, dizala). Bez odabira = kasnije se može uvesti ručno.',
        'none' => 'Bez predloška',
    ],
    'templates' => [
        'din276' => 'DIN 276 (Njemačka)',
    ],
    'asset_categories' => [
        'navigation' => 'Kategorije sredstava',
        'title' => 'Kategorije sredstava',
        'asset_type' => 'Tip sredstva',
        'category' => 'Kategorija',
        'unassigned' => 'Bez kategorije',
    ],
    'cost_centers' => [
        'navigation' => 'Mjesta troška',
        'title' => 'Mjesta troška',
        'code' => 'Šifra',
        'name' => 'Naziv',
        'parent' => 'Nadređeno',
        'empty' => 'Još nema uvezenih mjesta troška.',
    ],
    'fields' => [
        'code' => 'Šifra',
        'name' => 'Naziv',
    ],
    'actions' => [
        'create' => 'Novo',
        'edit' => 'Uredi',
        'import' => 'Uvezi predložak',
        'imported' => ':count zapisa uvezeno.',
    ],
];
