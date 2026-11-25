<?php

return [

    'child_assets' => 'Sotto-asset',
    'overview' => 'Panoramica',
    'sections' => [
        'location' => 'Posizione nell\'edificio',
        'lifecycle' => 'Ciclo di vita',
    ],

    'tabs' => [
        'general' => 'Generale',
        'maintenance' => 'Manutenzione e ciclo di vita',
    ],

    'actions' => [
        'new' => 'Nuovo',
        'open' => 'Apri',
        'create_asset_type_heading' => 'Crea nuovo tipo di asset',
        'create_company_heading' => 'Crea nuova azienda',
        'create_inspection_company_heading' => 'Crea nuovo organismo di ispezione',
    ],

    'fields' => [
        'id' => 'ID',
        'name' => 'Nome',
        'image' => 'Immagine',
        'asset_number' => 'N. asset',
        'type' => 'Tipo',
        'manufacturer' => 'Produttore',
        'serial_number' => 'Numero di serie',
        'serial_number_short' => 'N. serie',
        'maintenance_partner' => 'Partner di manutenzione',
        'inspection_company' => 'Organismo di ispezione',
        'last_maintenance' => 'Ultima manutenzione',
        'next_maintenance' => 'Prossima manutenzione',
        'kategorie' => 'Categoria',
        'gruppe' => 'Gruppo',
        'location' => 'Posizione',
        'parent_asset' => 'Impianto sovraordinato',
        'condition' => 'Stato',
        'service_status' => 'Stato di esercizio',
        'warranty_until' => 'Garanzia fino al',
        'installed_at' => 'Installato il',
        'decommissioned_at' => 'Dismesso il',
        'purchase_price' => 'Prezzo d\'acquisto',
        'category' => 'Lavorazione',
    ],

    'condition' => [
        'good' => 'Buono',
        'fair' => 'Discreto',
        'poor' => 'Scarso',
    ],

    'service_status' => [
        'in_operation' => 'In esercizio',
        'out_of_operation' => 'Fuori esercizio',
        'in_storage' => 'In magazzino',
        'disposed' => 'Smaltito',
    ],

    'filters' => [
        'all' => 'Tutti',
        'include_sub_locations' => 'incl. sottoubicazioni',
        'classification' => 'Classificazione',
        'maintenance_overdue' => 'Scaduta',
        'due_within' => 'In scadenza entro :days giorni',
        'maintenance_none' => 'Nessuna manutenzione pianificata',
        'warranty_expired' => 'Scaduta',
        'warranty_expiring' => 'Scade entro 90 giorni',
        'warranty_valid' => 'Valida',
        'not_specified' => 'Non specificato',
        'installed_from' => 'Installato dal',
        'installed_until' => 'Installato fino al',
        'hierarchy' => 'Gerarchia degli asset',
        'top_level_only' => 'Solo asset principali',
        'sub_assets_only' => 'Solo sotto-asset',
        'decommissioned' => 'Dismesso',
        'active' => 'In uso',
        'bim' => 'Modello BIM',
        'bim_linked' => 'Collegato a un elemento BIM',
        'bim_not_linked' => 'Senza collegamento BIM',
    ],

    'import' => [
        'help' => [
            'asset_number' => 'Serve solo ad aggiornare asset esistenti (opzione «Aggiorna asset esistenti»). I nuovi asset vengono numerati automaticamente.',
            'property' => 'Numero dell’immobile.',
            'location' => 'Nome o percorso dell’ubicazione nell’immobile, ad es. «Edificio A › PT › Corridoio» (vanno bene anche «/» o «>»).',
            'parent_asset' => 'N. o nome dell’asset padre nello stesso immobile.',
        ],
        'options' => [
            'update_existing' => 'Aggiorna asset esistenti tramite il numero (le celle vuote mantengono i valori)',
            'create_missing' => 'Crea automaticamente tipi di asset e aziende sconosciuti',
        ],
        'template' => [
            'action' => 'Modello di importazione',
            'heading' => 'Scarica modello di importazione',
            'description' => 'File CSV con tutte le colonne standard. Seleziona i tipi di asset per ottenere anche i loro campi specifici (ad es. carica dell’estintore) come colonne. I campi aggiuntivi vengono riconosciuti automaticamente dall’intestazione durante l’importazione.',
            'download' => 'Scarica',
            'asset_types' => 'Tipi di asset con campi aggiuntivi',
        ],
        'completed_title' => 'Importazione asset completata',
        'completed_body' => '{0} Nessun asset importato.|{1} :count asset importato.|[2,*] :count asset importati.',
        'failed_rows' => '{1} :count riga non riuscita.|[2,*] :count righe non riuscite.',
        'errors' => [
            'no_organization' => 'Nessun cliente impostato per l’importazione.',
            'property_not_found' => 'Immobile «:value» non trovato.',
            'location_without_property' => 'L’ubicazione «:value» non può essere assegnata senza immobile.',
            'location_not_found' => 'Ubicazione «:value» non trovata nell’immobile.',
            'location_ambiguous' => 'L’ubicazione «:value» non è univoca – indica il percorso (ad es. «Edificio A › PT › Corridoio»).',
            'asset_type_not_found' => 'Tipo di asset «:value» non trovato.',
            'parent_not_found' => 'Asset padre «:value» non trovato.',
            'company_not_found' => 'Azienda «:value» non trovata.',
            'invalid_number' => '«:value» non è un numero valido.',
            'invalid_boolean' => '«:value» non è un valore sì/no valido.',
            'invalid_date' => '«:value» non è una data valida.',
            'invalid_option' => '«:value» non è un’opzione valida.',
        ],
    ],

];
