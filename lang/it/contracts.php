<?php

return [

    'model' => 'Contratto',
    'models' => 'Contratti',
    'navigation' => 'Contratti',

    'sections' => [
        'master_data' => 'Dati contrattuali',
        'term' => 'Durata e disdetta',
        'costs' => 'Costi',
        'assignment' => 'Assegnazione e documenti',
    ],

    'fields' => [
        'name' => 'Denominazione',
        'contract_number' => 'Numero contratto',
        'partner_name' => 'Controparte contrattuale',
        'type' => 'Tipo di contratto',
        'status' => 'Stato',
        'start_date' => 'Inizio contratto',
        'end_date' => 'Fine contratto',
        'end_date_help' => 'Lasciare vuoto per contratti a tempo indeterminato.',
        'open_ended' => 'a tempo indeterminato',
        'notice_period_days' => 'Periodo di preavviso (giorni)',
        'notice_period_days_help' => 'Termine prima della fine del contratto entro cui va comunicata la disdetta.',
        'reminder_lead_days' => 'Anticipo promemoria (giorni)',
        'reminder_lead_days_help' => 'Numero di giorni prima del termine di disdetta in cui viene inviato il promemoria.',
        'notice_deadline' => 'Termine di disdetta',
        'amount' => 'Importo',
        'billing_cycle' => 'Periodo di fatturazione',
        'properties' => 'Immobili',
        'assets' => 'Asset',
        'notes' => 'Note',
    ],

    'type' => [
        'maintenance' => 'Contratto di manutenzione',
        'service' => 'Contratto di servizi',
        'rental' => 'Contratto di locazione',
        'insurance' => 'Assicurazione',
        'utility' => 'Utenze (elettricità/acqua/…)',
        'waste_disposal' => 'Smaltimento rifiuti',
        'other' => 'Altro',
    ],

    'status' => [
        'active' => 'Attivo',
        'terminated' => 'Disdetto',
        'expired' => 'Scaduto',
    ],

    'billing_cycle' => [
        'monthly' => 'Mensile',
        'quarterly' => 'Trimestrale',
        'yearly' => 'Annuale',
        'one_time' => 'Una tantum',
    ],

    'reminder' => [
        'title' => 'Termine di disdetta in scadenza: :name',
        'body' => 'Contratto con :partner — disdetta possibile entro i termini fino al :deadline (fine contratto :end). Verificare e decidere ora.',
    ],

    'stats' => [
        'active' => 'Contratti attivi',
        'needs_decision' => 'Termine di disdetta imminente',
        'yearly_costs' => 'Costi annui',
    ],

];
