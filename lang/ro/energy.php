<?php

return [

    'tab' => 'Energie',

    'meter' => [
        'model' => 'Contor',
        'models' => 'Contoare',
        'measured_assets' => 'Consumatori',
        'supplied_nodes' => 'Încăperi alimentate',
    ],

    'reading' => [
        'model' => 'Citire',
        'models' => 'Citiri',
    ],

    'readings' => [
        'none' => 'Nicio citire încă',
    ],

    'goal' => [
        'model' => 'Obiectiv',
        'models' => 'Obiective',
        'all_properties' => 'Toate obiectele',
        'asset' => 'Echipament',
        'max' => 'max.',
        'vs_year' => 'față de :year',
        'actual' => 'până acum',
        'forecast' => 'Estimare',
        'target' => 'Obiectiv',
        'empty' => 'Pentru acest an nu au fost definite încă obiective.',
    ],

    'pages' => [
        'record_readings' => 'Citire contoare',
    ],

    'sections' => [
        'master' => 'Date de bază',
        'formula' => 'Calcul',
    ],

    'fields' => [
        'name' => 'Denumire',
        'number' => 'Număr contor',
        'medium' => 'Tip de energie',
        'kind' => 'Tip contor',
        'parent' => 'Contor superior',
        'unit' => 'Unitatea contorului',
        'factor' => 'Factor de conversie',
        'location' => 'Amplasare',
        'device_asset' => 'Dispozitiv contor (asset)',
        'market_location_id' => 'Cod loc de consum (POD/CLC)',
        'calibration_valid_until' => 'Verificare metrologică valabilă până la',
        'is_active' => 'Activ',
        'notes' => 'Notițe',
        'sign' => 'Semn',
        'share' => 'Cotă',
        'last_reading' => 'Ultimul index',
        'read_at' => 'Data citirii',
        'value' => 'Index contor',
        'photo' => 'Fotografie contor',
        'note' => 'Observație',
        'source' => 'Sursă',
        'status' => 'Plauzibilitate',
        'created_by' => 'Înregistrat de',
        'consumption_year' => 'Consum :year',
        'goal_scope' => 'Se aplică pentru',
        'goal_type' => 'Tip obiectiv',
        'goal_period' => 'Perioadă',
        'year' => 'An',
        'target_value' => 'Valoare țintă',
        'baseline_year' => 'An de referință',
        'progress' => 'Progres',
    ],

    'help' => [
        'parent' => 'Contorul superior în arborele de contoare, de ex. contorul principal. Gol pentru contoarele principale.',
        'factor' => 'Convertește indexul în kWh, de ex. la gaz puterea calorifică × factorul de corecție (circa 10,5). La contoarele în kWh: 1.',
        'device_asset' => 'Opțional: dispozitivul ca asset, de ex. pentru plăcuța de identificare, eticheta QR și mentenanță.',
        'formula' => 'Un contor calculat nu are dispozitiv. Consumul său rezultă din alte contoare, de ex. contorul principal minus toate subcontoarele.',
        'share' => 'Dacă contorul măsoară mai multe echipamente sau zone, consumul său este repartizat conform acestor cote.',
        'goal_scope' => 'Lăsați gol pentru un obiectiv care se aplică tuturor obiectelor.',
        'market_location_id' => 'Codul locului de consum atribuit de operatorul de rețea (apare pe fiecare factură de energie electrică și gaz, de ex. POD/CLC). Doar pentru contoarele principale, de ex. pentru corelarea facturilor cu datele operatorului.',
    ],

    'medium' => [
        'electricity' => 'Energie electrică',
        'gas' => 'Gaz',
        'heat' => 'Căldură',
        'cold' => 'Răcire',
        'water' => 'Apă',
        'oil' => 'Combustibil lichid',
        'pv_generation' => 'Producție fotovoltaică',
    ],

    'kind' => [
        'main' => 'Contor principal',
        'sub' => 'Subcontor',
        'virtual' => 'Contor calculat',
    ],

    'source' => [
        'manual' => 'Mobil / web',
        'import' => 'Import',
        'api' => 'Interfață',
    ],

    'status' => [
        'plausible' => 'plauzibil',
        'suspicious' => 'suspect',
    ],

    'goal_type' => [
        'max_consumption' => 'Consum maxim',
        'max_intensity' => 'Indicator pe m²',
        'reduction' => 'Reducere față de anul de referință',
    ],

    'goal_period' => [
        'year' => 'An',
        'month' => 'pe lună',
    ],

    'goal_status' => [
        'good' => 'pe drumul cel bun',
        'warn' => 'în pericol',
        'crit' => 'depășit',
        'unknown' => 'fără referință',
    ],

    'plausibility' => [
        'decreased' => 'Indexul este mai mic decât cel anterior. Greșeală de tastare sau contor înlocuit?',
        'unusually_high' => 'Neobișnuit de mare: :actual :unit pe zi, de obicei circa :expected :unit. Verificați și faceți o fotografie.',
        'short' => [
            'decreased' => 'mai mic decât anterior',
            'unusually_high' => 'neobișnuit de mare',
        ],
    ],

    'actions' => [
        'record_reading' => 'Înregistrează citire',
        'save_reading' => 'Salvează indexul',
        'add_formula_term' => 'Adaugă contor',
        'attach_asset' => 'Asociază echipament',
        'attach_node' => 'Asociază încăpere',
        'edit_share' => 'Modifică cota',
        'import_readings' => 'Importă citiri',
        'open_in_energy' => 'Deschide în zona Energie',
        'all_goals' => 'Toate obiectivele',
    ],

    'notifications' => [
        'saved' => 'Index salvat',
        'saved_suspicious' => 'Index salvat, dar suspect',
    ],

    'errors' => [
        'virtual_meter' => 'Pentru contoarele calculate nu se pot înregistra indecși.',
    ],

    'filters' => [
        'calibration_due' => 'Verificare metrologică scadentă (180 de zile)',
        'from' => 'De la',
        'until' => 'Până la',
        'period' => 'Perioadă',
        'all_usage_types' => 'Toate tipurile de utilizare',
        'all_properties' => 'Toate obiectele',
        'periods' => [
            'this_month' => 'Luna aceasta',
            'last_month' => 'Luna trecută',
            'last_30_days' => 'Ultimele 30 de zile',
            'this_quarter' => 'Trimestrul acesta',
            'this_year' => 'Anul acesta',
            'last_year' => 'Anul trecut',
            'last_12_months' => 'Ultimele 12 luni',
            'custom' => 'Interval personalizat',
        ],
    ],

    'stats' => [
        'energy_in_period' => 'Energie în perioadă',
        'water_in_period' => 'Apă în perioadă',
        'goals_on_track' => 'Obiective pe drumul cel bun',
        'suspicious_readings' => 'Citiri suspecte',
        'calibration_due' => 'Verificare metrologică scadentă',
        'next_180_days' => 'în următoarele 180 de zile',
        'vs_previous_year' => ':change % față de anul anterior',
        'no_comparison' => 'nicio valoare din anul anterior',
        'year_to_date' => 'Consum :year până acum',
        'previous_year_to_date' => 'Aceeași perioadă :year',
    ],

    'charts' => [
        'consumption_trend' => 'Consum lunar',
        'consumption_per_day' => 'Consum pe zi',
        'meter_consumption' => 'Consum',
        'by_asset' => 'Cei mai mari consumatori',
        'consumption_in' => 'Consum în :unit',
        'previous_year' => 'Anul anterior',
        'last_30_days' => 'Ultimele 30 de zile',
        'last_12_months' => 'Ultimele 12 luni',
    ],

    'flow' => [
        'title' => 'Flux energetic',
        'description' => 'De la contoarele principale, prin subcontoare, până la echipamente. Lățime = cantitate.',
        'empty' => 'Pentru acest obiect și acest tip de energie nu există consum în perioada respectivă.',
        'unassigned' => 'nealocat',
    ],

    'consumers' => [
        'title' => 'Consumatori și flux energetic',
        'description' => 'Unde merge consumul acestui contor: prin subcontoare către echipamentele alocate. Lățime = cantitate.',
        'empty' => 'Acestui contor nu i-au fost alocați încă consumatori. Alocați echipamentele pe care le măsoară în fila „Consumatori” sau atașați subcontoare sub el.',
        'no_consumption' => 'Pentru acest contor nu există consum în anul selectat.',
        'list' => 'Cei mai mari consumatori',
        'hint' => 'Dacă un contor măsoară mai multe echipamente, consumul se împarte după cotele introduse. „:unassigned” este restul neexplicat de niciun echipament sau subcontor.',
    ],

    'comparison' => [
        'title' => 'Comparație imobile',
        'energy' => 'Energie în perioadă',
        'intensity' => 'pe m²',
        'change' => 'față de anul anterior',
    ],

    'calibration' => [
        'title' => 'Verificare metrologică scadentă',
        'empty' => 'Nicio verificare metrologică scadentă în următoarele 180 de zile',
    ],

    'suspicious' => [
        'title' => 'Citiri suspecte',
        'empty' => 'Nicio citire suspectă în perioadă',
    ],

    'import' => [
        'help' => [
            'meter' => 'Numărul contorului sau, alternativ, denumirea unică a contorului.',
        ],
        'completed_title' => 'Importul citirilor finalizat',
        'completed_body' => '{0} Nu a fost importată nicio citire.|{1} A fost importată :count citire.|[2,*] Număr de citiri importate: :count.',
        'errors' => [
            'meter_not_found' => 'Contorul „:value” nu a fost găsit.',
            'meter_ambiguous' => 'Contorul „:value” nu este unic – folosiți numărul contorului.',
        ],
    ],

];
