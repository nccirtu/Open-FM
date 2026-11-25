<?php

namespace App\Models;

use App\Enums\TicketCategory;
use App\Enums\TicketEstimateStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketSuspensionReason;
use App\Enums\TicketType;
use App\Flow\Concerns\HasWorkflows;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\TicketFactory;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Slimani\MediaManager\Concerns\InteractsWithMediaFiles;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use BelongsToOrganization, HasFactory;

    use HasUuids;
    use HasWorkflows;
    use InteractsWithMediaFiles;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'title', 'status', 'priority', 'category', 'type',
                'property_id', 'asset_id', 'assigned_to', 'company_id',
                'contract_id', 'due_at', 'resolution_notes',
                'suspension_reason', 'estimate_status', 'estimate_amount',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected $fillable = [
        'property_id',
        'asset_id',
        'structure_node_id',
        'type',
        'category',
        'priority',
        'status',
        'title',
        'description',
        'photos',
        'assigned_to',
        'company_id',
        'contract_id',
        'due_at',
        'resolution_notes',
        'suspension_reason',
        'estimate_status',
        'estimate_amount',
        'rating',
    ];

    protected $attributes = [
        'type' => 'fault',
        'priority' => 'p3',
        'status' => 'reported',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TicketType::class,
            'category' => TicketCategory::class,
            'priority' => TicketPriority::class,
            'status' => TicketStatus::class,
            'photos' => 'array',
            'due_at' => 'datetime',
            'resolved_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'seen_at' => 'datetime',
            'respond_due_at' => 'datetime',
            'resolve_due_at' => 'datetime',
            'first_response_at' => 'datetime',
            'sla_escalated_at' => 'datetime',
            'suspended_at' => 'datetime',
            'suspension_reason' => TicketSuspensionReason::class,
            'estimate_status' => TicketEstimateStatus::class,
            'estimate_amount' => 'decimal:2',
            'rating' => 'integer',
            'ticket_number' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Ticket $ticket): void {
            if (filled($ticket->ticket_number)) {
                return;
            }

            $organizationId = $ticket->organization_id ?? Filament::getTenant()?->getKey();

            if ($organizationId === null) {
                return;
            }

            $ticket->ticket_number = ((int) static::query()->withoutGlobalScopes()->where('organization_id', $organizationId)->max('ticket_number')) + 1;
        });
    }

    public function slaColor(): string
    {
        if ($this->resolve_due_at === null
            || $this->status === TicketStatus::Waiting
            || in_array($this->status, [TicketStatus::Resolved, TicketStatus::Closed], true)) {
            return 'gray';
        }

        return match (true) {
            $this->resolve_due_at->isPast() => 'danger',
            $this->resolve_due_at->lessThanOrEqualTo(now()->addDay()) => 'warning',
            default => 'success',
        };
    }

    public function isSlaBreached(): bool
    {
        if ($this->status === TicketStatus::Waiting
            || in_array($this->status, [TicketStatus::Resolved, TicketStatus::Closed], true)) {
            return false;
        }

        $responseBreached = $this->respond_due_at !== null
            && $this->first_response_at === null
            && $this->respond_due_at->isPast();

        $resolutionBreached = $this->resolve_due_at !== null && $this->resolve_due_at->isPast();

        return $responseBreached || $resolutionBreached;
    }

    public function isUnread(): bool
    {
        if ($this->seen_at === null) {
            return true;
        }

        return $this->last_activity_at !== null && $this->last_activity_at->greaterThan($this->seen_at);
    }

    /**
     * @param  Builder<Ticket>  $query
     */
    public function scopeReportedBy(Builder $query, User $user): void
    {
        $query->where($query->qualifyColumn('reported_by'), $user->getKey());
    }

    /**
     * @param  Builder<Ticket>  $query
     */
    public function scopeOnAssignedProperties(Builder $query, User $user): void
    {
        $assignedIds = $user->assignedPropertyIds();

        $assignedIds === null
            ? $query->whereRaw('1 = 0')
            : $query->whereIn($query->qualifyColumn('property_id'), $assignedIds);
    }

    /**
     * @param  Builder<Ticket>  $query
     */
    public function scopeVisibleInPortal(Builder $query, User $user): void
    {
        $query->where(function (Builder $query) use ($user): void {
            $query->reportedBy($user)->orWhere(fn (Builder $query) => $query->onAssignedProperties($user));
        });
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function structureNode(): BelongsTo
    {
        return $this->belongsTo(StructureNode::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class);
    }

    public function files(): MorphToMany
    {
        return $this->mediaFiles('files');
    }

    public function costEntries(): MorphMany
    {
        return $this->morphMany(CostEntry::class, 'costable');
    }
}
