<?php

return [

    'tab' => 'Energia',

    'meter' => [
        'model' => 'Contatore',
        'models' => 'Contatori',
        'measured_assets' => 'Utenze',
        'supplied_nodes' => 'Locali serviti',
    ],

    'reading' => [
        'model' => 'Lettura',
        'models' => 'Letture',
    ],

    'readings' => [
        'none' => 'Nessuna lettura',
    ],

    'goal' => [
        'model' => 'Obiettivo',
        'models' => 'Obiettivi',
        'all_properties' => 'Tutti gli immobili',
        'asset' => 'Impianto',
        'max' => 'max.',
        'vs_year' => 'vs :year',
        'actual' => 'finora',
        'forecast' => 'Proiezione',
        'target' => 'Obiettivo',
        'empty' => 'Per quest’anno non sono ancora stati definiti obiettivi.',
    ],

    'pages' => [
        'record_readings' => 'Lettura contatori',
    ],

    'sections' => [
        'master' => 'Dati anagrafici',
        'formula' => 'Calcolo',
    ],

    'fields' => [
        'name' => 'Denominazione',
        'number' => 'Matricola contatore',
        'medium' => 'Tipo di energia',
        'kind' => 'Tipo di contatore',
        'parent' => 'Contatore superiore',
        'unit' => 'Unità del totalizzatore',
        'factor' => 'Fattore di conversione',
        'location' => 'Posizione',
        'device_asset' => 'Dispositivo contatore (asset)',
        'market_location_id' => 'Codice POD / PDR',
        'calibration_valid_until' => 'Taratura valida fino al',
        'is_active' => 'Attivo',
        'notes' => 'Note',
        'sign' => 'Segno',
        'share' => 'Quota',
        'last_reading' => 'Ultima lettura',
        'read_at' => 'Data e ora',
        'value' => 'Valore letto',
        'photo' => 'Foto del contatore',
        'note' => 'Osservazione',
        'source' => 'Origine',
        'status' => 'Verifica',
        'created_by' => 'Rilevato da',
        'consumption_year' => 'Consumo :year',
        'goal_scope' => 'Si applica a',
        'goal_type' => 'Tipo di obiettivo',
        'goal_period' => 'Periodo',
        'year' => 'Anno',
        'target_value' => 'Valore obiettivo',
        'baseline_year' => 'Anno di riferimento',
        'progress' => 'Avanzamento',
    ],

    'help' => [
        'parent' => 'Contatore superiore nell’albero dei contatori, ad es. il contatore generale. Vuoto per i contatori generali.',
        'factor' => 'Converte la lettura in kWh, ad es. per il gas potere calorifico × coefficiente di conversione (circa 10,5). Per i contatori in kWh: 1.',
        'device_asset' => 'Facoltativo: il dispositivo come asset, ad es. per targhetta, etichetta QR e manutenzione.',
        'formula' => 'Un contatore calcolato non ha un dispositivo. Il suo consumo deriva da altri contatori, ad es. contatore generale meno tutti i sottocontatori.',
        'share' => 'Se il contatore misura più impianti o aree, il suo consumo viene ripartito secondo queste quote.',
        'goal_scope' => 'Lasciare vuoto per un obiettivo su tutti gli immobili.',
        'market_location_id' => 'Codice del punto di prelievo assegnato dal distributore (riportato su ogni bolletta di luce e gas, ad es. POD/PDR). Solo per i contatori principali, ad es. per abbinare bollette e dati del distributore.',
    ],

    'medium' => [
        'electricity' => 'Elettricità',
        'gas' => 'Gas',
        'heat' => 'Calore',
        'cold' => 'Freddo',
        'water' => 'Acqua',
        'oil' => 'Gasolio da riscaldamento',
        'pv_generation' => 'Produzione FV',
    ],

    'kind' => [
        'main' => 'Contatore generale',
        'sub' => 'Sottocontatore',
        'virtual' => 'Contatore calcolato',
    ],

    'source' => [
        'manual' => 'Smartphone / Web',
        'import' => 'Importazione',
        'api' => 'Interfaccia',
    ],

    'status' => [
        'plausible' => 'plausibile',
        'suspicious' => 'anomala',
    ],

    'goal_type' => [
        'max_consumption' => 'Consumo massimo',
        'max_intensity' => 'Indicatore per m²',
        'reduction' => 'Riduzione rispetto all’anno di riferimento',
    ],

    'goal_period' => [
        'year' => 'Anno',
        'month' => 'al mese',
    ],

    'goal_status' => [
        'good' => 'in linea',
        'warn' => 'a rischio',
        'crit' => 'superato',
        'unknown' => 'nessun riferimento',
    ],

    'plausibility' => [
        'decreased' => 'La lettura è inferiore a quella precedente. Errore di battitura o sostituzione del contatore?',
        'unusually_high' => 'Insolitamente alta: :actual :unit al giorno, di solito circa :expected :unit. Verificare e scattare una foto.',
        'short' => [
            'decreased' => 'inferiore alla precedente',
            'unusually_high' => 'insolitamente alta',
        ],
    ],

    'actions' => [
        'record_reading' => 'Registra lettura',
        'save_reading' => 'Salva lettura',
        'add_formula_term' => 'Aggiungi contatore',
        'attach_asset' => 'Assegna impianto',
        'attach_node' => 'Assegna locale',
        'edit_share' => 'Modifica quota',
        'import_readings' => 'Importa letture',
        'open_in_energy' => 'Apri nell’area Energia',
        'all_goals' => 'Tutti gli obiettivi',
    ],

    'notifications' => [
        'saved' => 'Lettura salvata',
        'saved_suspicious' => 'Lettura salvata, ma anomala',
    ],

    'errors' => [
        'virtual_meter' => 'Per i contatori calcolati non è possibile registrare letture.',
    ],

    'filters' => [
        'calibration_due' => 'Taratura in scadenza (180 giorni)',
        'from' => 'Dal',
        'until' => 'Al',
        'period' => 'Periodo',
        'all_usage_types' => 'Tutte le destinazioni d\'uso',
        'all_properties' => 'Tutti gli immobili',
        'periods' => [
            'this_month' => 'Questo mese',
            'last_month' => 'Mese scorso',
            'last_30_days' => 'Ultimi 30 giorni',
            'this_quarter' => 'Questo trimestre',
            'this_year' => 'Quest\'anno',
            'last_year' => 'Anno scorso',
            'last_12_months' => 'Ultimi 12 mesi',
            'custom' => 'Periodo personalizzato',
        ],
    ],

    'stats' => [
        'energy_in_period' => 'Energia nel periodo',
        'water_in_period' => 'Acqua nel periodo',
        'goals_on_track' => 'Obiettivi in linea',
        'suspicious_readings' => 'Letture anomale',
        'calibration_due' => 'Taratura in scadenza',
        'next_180_days' => 'nei prossimi 180 giorni',
        'vs_previous_year' => ':change % rispetto all’anno precedente',
        'no_comparison' => 'nessun valore dell’anno precedente',
        'year_to_date' => 'Consumo :year finora',
        'previous_year_to_date' => 'Stesso periodo :year',
    ],

    'charts' => [
        'consumption_trend' => 'Consumo mensile',
        'consumption_per_day' => 'Consumo giornaliero',
        'meter_consumption' => 'Consumo',
        'by_asset' => 'Maggiori utenze',
        'consumption_in' => 'Consumo in :unit',
        'previous_year' => 'Anno precedente',
        'last_30_days' => 'Ultimi 30 giorni',
        'last_12_months' => 'Ultimi 12 mesi',
    ],

    'flow' => [
        'title' => 'Flusso energetico',
        'description' => 'Dai contatori generali, attraverso i sottocontatori, fino agli impianti. Larghezza = quantità.',
        'empty' => 'Nessun consumo nel periodo per questo immobile e questo tipo di energia.',
        'unassigned' => 'non assegnato',
    ],

    'consumers' => [
        'title' => 'Utenze e flusso energetico',
        'description' => 'Dove va il consumo di questo contatore: attraverso i sottocontatori fino agli impianti assegnati. Larghezza = quantità.',
        'empty' => 'A questo contatore non sono ancora assegnate utenze. Assegna gli impianti che misura nella scheda «Utenze» oppure collega dei sottocontatori.',
        'no_consumption' => 'Nessun consumo per questo contatore nell\'anno selezionato.',
        'list' => 'Maggiori utenze',
        'hint' => 'Se un contatore misura più impianti, il consumo viene ripartito secondo le quote indicate. «:unassigned» è la parte residua non spiegata da impianti o sottocontatori.',
    ],

    'comparison' => [
        'title' => 'Confronto immobili',
        'energy' => 'Energia nel periodo',
        'intensity' => 'per m²',
        'change' => 'vs anno precedente',
    ],

    'calibration' => [
        'title' => 'Taratura in scadenza',
        'empty' => 'Nessuna taratura in scadenza nei prossimi 180 giorni',
    ],

    'suspicious' => [
        'title' => 'Letture anomale',
        'empty' => 'Nessuna lettura anomala nel periodo',
    ],

    'import' => [
        'help' => [
            'meter' => 'Matricola del contatore oppure, in alternativa, la denominazione univoca del contatore.',
        ],
        'completed_title' => 'Importazione delle letture completata',
        'completed_body' => '{0} Nessuna lettura importata.|{1} :count lettura importata.|[2,*] :count letture importate.',
        'errors' => [
            'meter_not_found' => 'Contatore «:value» non trovato.',
            'meter_ambiguous' => 'Il contatore «:value» non è univoco – utilizzare la matricola.',
        ],
    ],

];
