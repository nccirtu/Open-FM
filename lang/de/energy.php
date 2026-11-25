<?php

return [

    'tab' => 'Energie',

    'meter' => [
        'model' => 'Zähler',
        'models' => 'Zähler',
        'measured_assets' => 'Verbraucher',
        'supplied_nodes' => 'Versorgte Räume',
    ],

    'reading' => [
        'model' => 'Ablesung',
        'models' => 'Ablesungen',
    ],

    'readings' => [
        'none' => 'Noch keine Ablesung',
    ],

    'goal' => [
        'model' => 'Ziel',
        'models' => 'Ziele',
        'all_properties' => 'Alle Objekte',
        'asset' => 'Anlage',
        'max' => 'max.',
        'vs_year' => 'ggü. :year',
        'actual' => 'bisher',
        'forecast' => 'Hochrechnung',
        'target' => 'Ziel',
        'empty' => 'Für dieses Jahr sind noch keine Ziele angelegt.',
    ],

    'pages' => [
        'record_readings' => 'Ablesen',
    ],

    'sections' => [
        'master' => 'Stammdaten',
        'formula' => 'Berechnung',
    ],

    'fields' => [
        'name' => 'Bezeichnung',
        'number' => 'Zählernummer',
        'medium' => 'Energieart',
        'kind' => 'Zählerart',
        'parent' => 'Hängt unter',
        'unit' => 'Einheit des Zählwerks',
        'factor' => 'Umrechnungsfaktor',
        'location' => 'Standort',
        'device_asset' => 'Zählergerät (Asset)',
        'market_location_id' => 'Marktlokations-ID (MaLo-ID)',
        'calibration_valid_until' => 'Eichung gültig bis',
        'is_active' => 'Aktiv',
        'notes' => 'Notizen',
        'sign' => 'Rechenzeichen',
        'share' => 'Anteil',
        'last_reading' => 'Letzter Stand',
        'read_at' => 'Zeitpunkt',
        'value' => 'Zählerstand',
        'photo' => 'Foto vom Zähler',
        'note' => 'Bemerkung',
        'source' => 'Quelle',
        'status' => 'Prüfung',
        'created_by' => 'Erfasst von',
        'consumption_year' => 'Verbrauch :year',
        'goal_scope' => 'Gilt für',
        'goal_type' => 'Art des Ziels',
        'goal_period' => 'Zeitraum',
        'year' => 'Jahr',
        'target_value' => 'Zielwert',
        'baseline_year' => 'Basisjahr',
        'progress' => 'Fortschritt',
    ],

    'help' => [
        'parent' => 'Übergeordneter Zähler im Zählerbaum, z. B. der Hauptzähler. Leer bei Hauptzählern.',
        'factor' => 'Rechnet den Zählerstand in kWh um, z. B. bei Gas Brennwert × Zustandszahl (etwa 10,5). Bei kWh-Zählern 1.',
        'device_asset' => 'Optional: das Gerät als Asset, z. B. für Typenschild, QR-Etikett und Wartung.',
        'formula' => 'Ein berechneter Zähler hat kein Gerät. Sein Verbrauch ergibt sich aus anderen Zählern, z. B. Hauptzähler minus alle Unterzähler.',
        'share' => 'Misst der Zähler mehrere Anlagen oder Bereiche, wird sein Verbrauch nach diesen Anteilen aufgeteilt.',
        'goal_scope' => 'Leer lassen für ein Ziel über alle Objekte.',
        'market_location_id' => 'Kennung des Netzanschlusses, die der Netzbetreiber vergibt (11-stellig, steht auf jeder Strom- und Gasrechnung). Nur bei Hauptzählern, z. B. um Rechnungen und Daten des Netzbetreibers zuzuordnen.',
    ],

    'medium' => [
        'electricity' => 'Strom',
        'gas' => 'Gas',
        'heat' => 'Wärme',
        'cold' => 'Kälte',
        'water' => 'Wasser',
        'oil' => 'Heizöl',
        'pv_generation' => 'PV-Erzeugung',
    ],

    'kind' => [
        'main' => 'Hauptzähler',
        'sub' => 'Unterzähler',
        'virtual' => 'Berechneter Zähler',
    ],

    'source' => [
        'manual' => 'Handy / Web',
        'import' => 'Import',
        'api' => 'Schnittstelle',
    ],

    'status' => [
        'plausible' => 'plausibel',
        'suspicious' => 'auffällig',
    ],

    'goal_type' => [
        'max_consumption' => 'Höchstmenge',
        'max_intensity' => 'Kennwert je m²',
        'reduction' => 'Einsparung gegenüber Basisjahr',
    ],

    'goal_period' => [
        'year' => 'Jahr',
        'month' => 'je Monat',
    ],

    'goal_status' => [
        'good' => 'auf Kurs',
        'warn' => 'gefährdet',
        'crit' => 'überschritten',
        'unknown' => 'keine Basis',
    ],

    'plausibility' => [
        'decreased' => 'Der Stand ist kleiner als der vorherige. Tippfehler oder Zählertausch?',
        'unusually_high' => 'Ungewöhnlich hoch: :actual :unit pro Tag, üblich sind etwa :expected :unit. Bitte prüfen und ein Foto aufnehmen.',
        'short' => [
            'decreased' => 'kleiner als vorher',
            'unusually_high' => 'ungewöhnlich hoch',
        ],
    ],

    'actions' => [
        'record_reading' => 'Ablesung erfassen',
        'save_reading' => 'Stand speichern',
        'add_formula_term' => 'Zähler hinzufügen',
        'attach_asset' => 'Anlage zuordnen',
        'attach_node' => 'Raum zuordnen',
        'edit_share' => 'Anteil ändern',
        'import_readings' => 'Ablesungen importieren',
        'open_in_energy' => 'Im Energie-Bereich öffnen',
        'all_goals' => 'Alle Ziele',
    ],

    'notifications' => [
        'saved' => 'Stand gespeichert',
        'saved_suspicious' => 'Stand gespeichert, aber auffällig',
    ],

    'errors' => [
        'virtual_meter' => 'Für berechnete Zähler können keine Stände erfasst werden.',
    ],

    'filters' => [
        'calibration_due' => 'Eichung fällig (180 Tage)',
        'from' => 'Von',
        'until' => 'Bis',
        'period' => 'Zeitraum',
        'all_usage_types' => 'Alle Nutzungsarten',
        'all_properties' => 'Alle Objekte',
        'periods' => [
            'this_month' => 'Dieser Monat',
            'last_month' => 'Letzter Monat',
            'last_30_days' => 'Letzte 30 Tage',
            'this_quarter' => 'Dieses Quartal',
            'this_year' => 'Dieses Jahr',
            'last_year' => 'Letztes Jahr',
            'last_12_months' => 'Letzte 12 Monate',
            'custom' => 'Eigener Zeitraum',
        ],
    ],

    'stats' => [
        'energy_in_period' => 'Energie im Zeitraum',
        'water_in_period' => 'Wasser im Zeitraum',
        'goals_on_track' => 'Ziele auf Kurs',
        'suspicious_readings' => 'Auffällige Ablesungen',
        'calibration_due' => 'Eichung fällig',
        'next_180_days' => 'in den nächsten 180 Tagen',
        'vs_previous_year' => ':change % ggü. Vorjahr',
        'no_comparison' => 'kein Vorjahreswert',
        'year_to_date' => 'Verbrauch :year bisher',
        'previous_year_to_date' => 'Gleicher Zeitraum :year',
    ],

    'charts' => [
        'consumption_trend' => 'Verbrauch je Monat',
        'consumption_per_day' => 'Verbrauch je Tag',
        'meter_consumption' => 'Verbrauch',
        'by_asset' => 'Größte Verbraucher',
        'consumption_in' => 'Verbrauch in :unit',
        'previous_year' => 'Vorjahr',
        'last_30_days' => 'Letzte 30 Tage',
        'last_12_months' => 'Letzte 12 Monate',
    ],

    'flow' => [
        'title' => 'Energiefluss',
        'description' => 'Von den Hauptzählern über die Unterzähler bis zu den Anlagen. Breite = Menge.',
        'empty' => 'Für dieses Objekt und diese Energieart gibt es im Zeitraum keinen Verbrauch.',
        'unassigned' => 'nicht zugeordnet',
    ],

    'consumers' => [
        'title' => 'Verbraucher & Energiefluss',
        'description' => 'Wohin der Verbrauch dieses Zählers fließt: über Unterzähler zu den zugeordneten Anlagen. Breite = Menge.',
        'empty' => 'Diesem Zähler sind noch keine Verbraucher zugeordnet. Ordne im Reiter „Verbraucher“ die Anlagen zu, die er misst, oder hänge Unterzähler darunter.',
        'no_consumption' => 'Im gewählten Jahr gibt es für diesen Zähler noch keinen Verbrauch.',
        'list' => 'Größte Verbraucher',
        'hint' => 'Misst ein Zähler mehrere Anlagen, wird sein Verbrauch nach den hinterlegten Anteilen aufgeteilt. „:unassigned“ ist der Rest, den keine Anlage und kein Unterzähler erklärt.',
    ],

    'comparison' => [
        'title' => 'Objekte im Vergleich',
        'energy' => 'Energie im Zeitraum',
        'intensity' => 'je m²',
        'change' => 'ggü. Vorjahr',
    ],

    'calibration' => [
        'title' => 'Eichung fällig',
        'empty' => 'Keine Eichung in den nächsten 180 Tagen fällig',
    ],

    'suspicious' => [
        'title' => 'Auffällige Ablesungen',
        'empty' => 'Keine auffälligen Ablesungen im Zeitraum',
    ],

    'import' => [
        'help' => [
            'meter' => 'Zählernummer, ersatzweise die eindeutige Bezeichnung des Zählers.',
        ],
        'completed_title' => 'Import der Ablesungen abgeschlossen',
        'completed_body' => '{0} Es wurde kein Stand importiert.|{1} :count Stand wurde importiert.|[2,*] :count Stände wurden importiert.',
        'errors' => [
            'meter_not_found' => 'Zähler „:value“ nicht gefunden.',
            'meter_ambiguous' => 'Zähler „:value“ ist nicht eindeutig – bitte die Zählernummer verwenden.',
        ],
    ],

];
