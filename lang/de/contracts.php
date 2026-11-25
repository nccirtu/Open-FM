<?php

return [

    'model' => 'Vertrag',
    'models' => 'Verträge',
    'navigation' => 'Verträge',

    'sections' => [
        'master_data' => 'Vertragsdaten',
        'term' => 'Laufzeit & Kündigung',
        'costs' => 'Kosten',
        'assignment' => 'Zuordnung & Unterlagen',
    ],

    'fields' => [
        'name' => 'Bezeichnung',
        'contract_number' => 'Vertragsnummer',
        'partner_name' => 'Vertragspartner',
        'type' => 'Vertragsart',
        'status' => 'Status',
        'start_date' => 'Vertragsbeginn',
        'end_date' => 'Vertragsende',
        'end_date_help' => 'Leer lassen bei unbefristeten Verträgen.',
        'open_ended' => 'unbefristet',
        'notice_period_days' => 'Kündigungsfrist (Tage)',
        'notice_period_days_help' => 'Frist vor Vertragsende, innerhalb derer gekündigt werden muss.',
        'reminder_lead_days' => 'Erinnerungs-Vorlauf (Tage)',
        'reminder_lead_days_help' => 'So viele Tage vor dem Kündigungsstichtag wird erinnert.',
        'notice_deadline' => 'Kündigungsstichtag',
        'amount' => 'Betrag',
        'billing_cycle' => 'Abrechnungszeitraum',
        'properties' => 'Objekte',
        'assets' => 'Assets',
        'notes' => 'Bemerkungen',
    ],

    'type' => [
        'maintenance' => 'Wartungsvertrag',
        'service' => 'Dienstleistungsvertrag',
        'rental' => 'Mietvertrag',
        'insurance' => 'Versicherung',
        'utility' => 'Versorgung (Strom/Wasser/…)',
        'waste_disposal' => 'Entsorgung',
        'other' => 'Sonstiges',
    ],

    'status' => [
        'active' => 'Aktiv',
        'terminated' => 'Gekündigt',
        'expired' => 'Abgelaufen',
    ],

    'billing_cycle' => [
        'monthly' => 'Monatlich',
        'quarterly' => 'Quartalsweise',
        'yearly' => 'Jährlich',
        'one_time' => 'Einmalig',
    ],

    'reminder' => [
        'title' => 'Kündigungsfrist läuft: :name',
        'body' => 'Vertrag mit :partner — Kündigung fristgerecht bis :deadline möglich (Vertragsende :end). Jetzt prüfen und entscheiden.',
    ],

    'stats' => [
        'active' => 'Aktive Verträge',
        'needs_decision' => 'Kündigungsstichtag steht an',
        'yearly_costs' => 'Kosten pro Jahr',
    ],

];
