<?php

namespace App\Models;

use App\Enums\TicketPriority;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\SlaDefinitionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlaDefinition extends Model
{
    /** @use HasFactory<SlaDefinitionFactory> */
    use BelongsToOrganization, HasFactory;

    use HasUuids;

    protected $fillable = [
        'priority',
        'response_hours',
        'resolution_hours',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'priority' => TicketPriority::class,
            'is_active' => 'boolean',
        ];
    }

    public static function forTicket(Ticket $ticket): ?self
    {
        if ($ticket->priority === null || $ticket->organization_id === null) {
            return null;
        }

        return self::query()
            ->withoutGlobalScopes()
            ->where('organization_id', $ticket->organization_id)
            ->where('priority', $ticket->priority)
            ->where('is_active', true)
            ->first();
    }
}
