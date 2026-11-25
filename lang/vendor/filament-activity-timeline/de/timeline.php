<?php

declare(strict_types=1);

return [

    'heading' => 'Aktivitäten',

    'events' => [
        'created' => 'Erstellt',
        'updated' => 'Aktualisiert',
        'deleted' => 'Gelöscht',
        'restored' => 'Wiederhergestellt',
    ],

    // Verfügbare Variablen:
    // :causer :subject :subject_label :subject_title :event :changes_count :date
    'sentences' => [
        'created' => ':causer hat :subject gemeldet.',
        'updated' => ':causer hat :subject aktualisiert.',
        'deleted' => ':causer hat :subject gelöscht.',
        'restored' => ':causer hat :subject wiederhergestellt.',
        'default' => ':causer hat ":event" auf :subject ausgeführt.',
    ],

    'causer' => [
        'system' => 'System',
        'unknown' => 'Jemand',
    ],

    'subject' => [
        'unknown' => 'einen Datensatz',
        'fallback' => ':label #:id',
    ],

    'changes' => [
        'heading' => 'Änderungen',
        'count' => '{0} Keine Änderung|{1} :count Änderung|[2,*] :count Änderungen',
        'from_to' => ':old zu :new',
        'set_to' => 'gesetzt auf :new',
        'cleared' => 'geleert',
    ],

    'values' => [
        'null' => 'Nicht gesetzt',
        'empty' => 'Leer',
        'true' => 'Ja',
        'false' => 'Nein',
        'list_empty' => 'Keine',
        'redacted' => 'Verborgen',
    ],

    'filters' => [
        'label' => 'Aktivitäten filtern',
        'all' => 'Alle Ereignisse',
    ],

    'actions' => [
        'load_more' => 'Mehr laden',
        'loading' => 'Lädt',
    ],

    'empty' => [
        'heading' => 'Noch keine Aktivitäten',
        'description' => 'Aktionen an diesem Datensatz erscheinen hier.',
    ],

    'error' => [
        'heading' => 'Aktivitäten konnten nicht geladen werden',
        'description' => 'Die Aktivitätsquelle konnte nicht gelesen werden.',
    ],

    'meta' => [
        'occurred_at' => 'Am :date',
    ],

    'debug' => [
        'heading' => 'Darstellungs-Diagnose',
        'model_label' => 'Modell-Bezeichnung',
        'record_title' => 'Datensatz-Titel',
        'source' => 'Quelle',
        'renderer' => 'Satz-Renderer',
        'properties' => 'Verfügbare Eigenschaften',
    ],

];
