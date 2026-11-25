<?php

return [

    'tab' => 'Energie',

    'meter' => [
        'model' => 'Meter',
        'models' => 'Meters',
        'measured_assets' => 'Verbruikers',
        'supplied_nodes' => 'Bediende ruimtes',
    ],

    'reading' => [
        'model' => 'Meteropname',
        'models' => 'Meteropnames',
    ],

    'readings' => [
        'none' => 'Nog geen meteropname',
    ],

    'goal' => [
        'model' => 'Doel',
        'models' => 'Doelen',
        'all_properties' => 'Alle objecten',
        'asset' => 'Installatie',
        'max' => 'max.',
        'vs_year' => 't.o.v. :year',
        'actual' => 'tot nu toe',
        'forecast' => 'Prognose',
        'target' => 'Doel',
        'empty' => 'Voor dit jaar zijn nog geen doelen vastgelegd.',
    ],

    'pages' => [
        'record_readings' => 'Meters opnemen',
    ],

    'sections' => [
        'master' => 'Stamgegevens',
        'formula' => 'Berekening',
    ],

    'fields' => [
        'name' => 'Omschrijving',
        'number' => 'Meternummer',
        'medium' => 'Energiesoort',
        'kind' => 'Metertype',
        'parent' => 'Valt onder',
        'unit' => 'Eenheid telwerk',
        'factor' => 'Omrekenfactor',
        'location' => 'Locatie',
        'device_asset' => 'Meterapparaat (asset)',
        'market_location_id' => 'EAN-code aansluiting',
        'calibration_valid_until' => 'IJking geldig tot',
        'is_active' => 'Actief',
        'notes' => 'Notities',
        'sign' => 'Rekenteken',
        'share' => 'Aandeel',
        'last_reading' => 'Laatste stand',
        'read_at' => 'Tijdstip',
        'value' => 'Meterstand',
        'photo' => 'Foto van de meter',
        'note' => 'Opmerking',
        'source' => 'Bron',
        'status' => 'Controle',
        'created_by' => 'Vastgelegd door',
        'consumption_year' => 'Verbruik :year',
        'goal_scope' => 'Geldt voor',
        'goal_type' => 'Soort doel',
        'goal_period' => 'Periode',
        'year' => 'Jaar',
        'target_value' => 'Streefwaarde',
        'baseline_year' => 'Basisjaar',
        'progress' => 'Voortgang',
    ],

    'help' => [
        'parent' => 'Bovenliggende meter in de meterboom, bijv. de hoofdmeter. Leeg bij hoofdmeters.',
        'factor' => 'Rekent de meterstand om naar kWh, bijv. bij gas calorische waarde × correctiefactor (ca. 10,5). Bij kWh-meters 1.',
        'device_asset' => 'Optioneel: het apparaat als asset, bijv. voor typeplaatje, QR-label en onderhoud.',
        'formula' => 'Een berekende meter heeft geen apparaat. Het verbruik volgt uit andere meters, bijv. hoofdmeter min alle submeters.',
        'share' => 'Meet de meter meerdere installaties of ruimtes, dan wordt het verbruik volgens deze aandelen verdeeld.',
        'goal_scope' => 'Leeg laten voor een doel over alle objecten.',
        'market_location_id' => 'Aansluitingscode die de netbeheerder toekent (staat op elke stroom- en gasrekening, bijv. EAN-code). Alleen voor hoofdmeters, bijv. om facturen en gegevens van de netbeheerder te koppelen.',
    ],

    'medium' => [
        'electricity' => 'Elektriciteit',
        'gas' => 'Gas',
        'heat' => 'Warmte',
        'cold' => 'Koude',
        'water' => 'Water',
        'oil' => 'Stookolie',
        'pv_generation' => 'PV-opwek',
    ],

    'kind' => [
        'main' => 'Hoofdmeter',
        'sub' => 'Submeter',
        'virtual' => 'Berekende meter',
    ],

    'source' => [
        'manual' => 'Mobiel / web',
        'import' => 'Import',
        'api' => 'Koppeling',
    ],

    'status' => [
        'plausible' => 'plausibel',
        'suspicious' => 'afwijkend',
    ],

    'goal_type' => [
        'max_consumption' => 'Maximaal verbruik',
        'max_intensity' => 'Kengetal per m²',
        'reduction' => 'Besparing t.o.v. basisjaar',
    ],

    'goal_period' => [
        'year' => 'Jaar',
        'month' => 'per maand',
    ],

    'goal_status' => [
        'good' => 'op koers',
        'warn' => 'in gevaar',
        'crit' => 'overschreden',
        'unknown' => 'geen basis',
    ],

    'plausibility' => [
        'decreased' => 'De stand is lager dan de vorige. Typefout of metervervanging?',
        'unusually_high' => 'Ongebruikelijk hoog: :actual :unit per dag, normaal is ca. :expected :unit. Controleer dit en maak een foto.',
        'short' => [
            'decreased' => 'lager dan vorige',
            'unusually_high' => 'ongebruikelijk hoog',
        ],
    ],

    'actions' => [
        'record_reading' => 'Meterstand vastleggen',
        'save_reading' => 'Stand opslaan',
        'add_formula_term' => 'Meter toevoegen',
        'attach_asset' => 'Installatie koppelen',
        'attach_node' => 'Ruimte koppelen',
        'edit_share' => 'Aandeel wijzigen',
        'import_readings' => 'Meteropnames importeren',
        'open_in_energy' => 'Openen in Energie',
        'all_goals' => 'Alle doelen',
    ],

    'notifications' => [
        'saved' => 'Stand opgeslagen',
        'saved_suspicious' => 'Stand opgeslagen, maar afwijkend',
    ],

    'errors' => [
        'virtual_meter' => 'Voor berekende meters kunnen geen standen worden vastgelegd.',
    ],

    'filters' => [
        'calibration_due' => 'IJking verloopt (180 dagen)',
        'from' => 'Van',
        'until' => 'Tot',
        'period' => 'Periode',
        'all_usage_types' => 'Alle gebruikstypen',
        'all_properties' => 'Alle objecten',
        'periods' => [
            'this_month' => 'Deze maand',
            'last_month' => 'Vorige maand',
            'last_30_days' => 'Laatste 30 dagen',
            'this_quarter' => 'Dit kwartaal',
            'this_year' => 'Dit jaar',
            'last_year' => 'Vorig jaar',
            'last_12_months' => 'Laatste 12 maanden',
            'custom' => 'Eigen periode',
        ],
    ],

    'stats' => [
        'energy_in_period' => 'Energie in periode',
        'water_in_period' => 'Water in periode',
        'goals_on_track' => 'Doelen op koers',
        'suspicious_readings' => 'Afwijkende meteropnames',
        'calibration_due' => 'IJking verloopt',
        'next_180_days' => 'in de komende 180 dagen',
        'vs_previous_year' => ':change % t.o.v. vorig jaar',
        'no_comparison' => 'geen waarde vorig jaar',
        'year_to_date' => 'Verbruik :year tot nu toe',
        'previous_year_to_date' => 'Zelfde periode :year',
    ],

    'charts' => [
        'consumption_trend' => 'Verbruik per maand',
        'consumption_per_day' => 'Verbruik per dag',
        'meter_consumption' => 'Verbruik',
        'by_asset' => 'Grootste verbruikers',
        'consumption_in' => 'Verbruik in :unit',
        'previous_year' => 'Vorig jaar',
        'last_30_days' => 'Laatste 30 dagen',
        'last_12_months' => 'Laatste 12 maanden',
    ],

    'flow' => [
        'title' => 'Energiestroom',
        'description' => 'Van de hoofdmeters via de submeters tot aan de installaties. Breedte = hoeveelheid.',
        'empty' => 'Voor dit object en deze energiesoort is er in deze periode geen verbruik.',
        'unassigned' => 'niet toegewezen',
    ],

    'consumers' => [
        'title' => 'Verbruikers en energiestroom',
        'description' => 'Waar het verbruik van deze meter naartoe gaat: via tussenmeters naar de toegewezen installaties. Breedte = hoeveelheid.',
        'empty' => 'Aan deze meter zijn nog geen verbruikers toegewezen. Wijs op het tabblad „Verbruikers” de installaties toe die hij meet, of hang tussenmeters eronder.',
        'no_consumption' => 'Voor deze meter is er in het gekozen jaar nog geen verbruik.',
        'list' => 'Grootste verbruikers',
        'hint' => 'Meet een meter meerdere installaties, dan wordt het verbruik volgens de opgegeven aandelen verdeeld. „:unassigned” is de rest die door geen installatie of tussenmeter wordt verklaard.',
    ],

    'comparison' => [
        'title' => 'Objecten vergeleken',
        'energy' => 'Energie in periode',
        'intensity' => 'per m²',
        'change' => 't.o.v. vorig jaar',
    ],

    'calibration' => [
        'title' => 'IJking verloopt',
        'empty' => 'Geen ijking die in de komende 180 dagen verloopt',
    ],

    'suspicious' => [
        'title' => 'Afwijkende meteropnames',
        'empty' => 'Geen opvallende meterstanden in deze periode',
    ],

    'import' => [
        'help' => [
            'meter' => 'Meternummer, of anders de unieke omschrijving van de meter.',
        ],
        'completed_title' => 'Import van meteropnames voltooid',
        'completed_body' => '{0} Er is geen stand geïmporteerd.|{1} :count stand is geïmporteerd.|[2,*] :count standen zijn geïmporteerd.',
        'errors' => [
            'meter_not_found' => 'Meter „:value” niet gevonden.',
            'meter_ambiguous' => 'Meter „:value” is niet eenduidig – gebruik het meternummer.',
        ],
    ],

];
