<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\TicketAutomationRuleFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketAutomationRule extends Model
{
    /** @use HasFactory<TicketAutomationRuleFactory> */
    use BelongsToOrganization, HasFactory;

    use HasUuids;

    public const EVENT_CREATED = 'created';

    public const EVENT_STATUS_CHANGED = 'status_changed';

    public const EVENT_ASSIGNED = 'assigned';

    public const EVENT_REPLY_ADDED = 'reply_added';

    public const EVENTS = [
        self::EVENT_CREATED,
        self::EVENT_STATUS_CHANGED,
        self::EVENT_ASSIGNED,
        self::EVENT_REPLY_ADDED,
    ];

    public const CONDITION_FIELDS = ['category', 'priority', 'type', 'status'];

    public const ACTION_TYPES = ['assign_to', 'set_status', 'set_priority', 'add_internal_note'];

    protected $fillable = [
        'name',
        'is_active',
        'event',
        'conditions',
        'actions',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'conditions' => 'array',
            'actions' => 'array',
        ];
    }
}
