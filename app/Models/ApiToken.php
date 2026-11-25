<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class ApiToken extends SanctumPersonalAccessToken
{
    use BelongsToOrganization;

    protected $table = 'personal_access_tokens';

    public const RESOURCES = [
        'properties' => 'Objekte und Struktur',
        'assets' => 'Anlagen und Anlagentypen',
        'classification' => 'Klassifikation',
        'companies' => 'Firmen, Adressen und Ansprechpartner',
        'tickets' => 'Störungen und SLA',
        'maintenance' => 'Wartungspläne und -aufträge',
        'contracts' => 'Verträge',
        'projects' => 'Projekte, Phasen, Aufgaben und Kosten',
        'energy' => 'Energie: Zähler und Zählerstände',
        'users' => 'Benutzer',
        'organization' => 'Mandant',
    ];

    /**
     * @var array<string, string>
     */
    public const ABILITIES = [
        'properties:read' => 'Objekte und Struktur lesen',
        'properties:write' => 'Objekte und Struktur schreiben',
        'assets:read' => 'Anlagen und Anlagentypen lesen',
        'assets:write' => 'Anlagen und Anlagentypen schreiben',
        'classification:read' => 'Klassifikation lesen',
        'companies:read' => 'Firmen lesen',
        'companies:write' => 'Firmen schreiben',
        'tickets:read' => 'Störungen lesen',
        'tickets:write' => 'Störungen schreiben',
        'maintenance:read' => 'Wartung lesen',
        'maintenance:write' => 'Wartung schreiben',
        'contracts:read' => 'Verträge lesen',
        'contracts:write' => 'Verträge schreiben',
        'projects:read' => 'Projekte lesen',
        'projects:write' => 'Projekte schreiben',
        'energy:read' => 'Zähler und Zählerstände lesen',
        'energy:write' => 'Zählerstände einspielen',
        'waste:read' => 'Abfall lesen (Behälter, Termine, Leerungen)',
        'waste:write' => 'Leerungen und Füllstände einspielen',
        'users:read' => 'Benutzer lesen',
        'organization:read' => 'Mandant lesen',
    ];

    public static function maxLifetimeDays(): int
    {
        return max((int) config('api.token_max_days', 365), 0);
    }
}
