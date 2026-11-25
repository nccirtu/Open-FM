<?php

namespace App\Support\Portal;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

final class TicketStatusHistory
{
    /**
     * @return Collection<int, array{ticket_id: string, at: CarbonInterface, from: ?TicketStatus, to: TicketStatus}>
     */
    public static function forTicket(Ticket $ticket): Collection
    {
        $activities = Activity::query()
            ->where('subject_type', $ticket->getMorphClass())
            ->where('subject_id', $ticket->getKey())
            ->oldest()
            ->oldest('id')
            ->get();

        return self::statusChanges($activities, includeCreation: true)->values();
    }

    /**
     * @param  Builder<Ticket>  $tickets
     * @return Collection<int, array{ticket_id: string, at: CarbonInterface, from: ?TicketStatus, to: TicketStatus}>
     */
    public static function recent(Builder $tickets, int $limit = 8, int $days = 90): Collection
    {
        $activities = Activity::query()
            ->where('subject_type', (new Ticket)->getMorphClass())
            ->whereIn('subject_id', $tickets->select($tickets->qualifyColumn('id')))
            ->where('event', 'updated')
            ->where('created_at', '>=', now()->subDays($days))
            ->latest()
            ->latest('id')
            ->limit($limit * 6)
            ->get();

        return self::statusChanges($activities, includeCreation: false)->take($limit)->values();
    }

    /**
     * @param  Collection<int, Activity>  $activities
     * @return Collection<int, array{ticket_id: string, at: CarbonInterface, from: ?TicketStatus, to: TicketStatus}>
     */
    private static function statusChanges(Collection $activities, bool $includeCreation): Collection
    {
        return $activities
            ->map(function (Activity $activity) use ($includeCreation): ?array {
                $changes = $activity->attribute_changes;
                $to = TicketStatus::tryFrom((string) ($changes?->get('attributes')['status'] ?? ''));

                if ($to === null || ($activity->event === 'created' && ! $includeCreation)) {
                    return null;
                }

                return [
                    'ticket_id' => (string) $activity->subject_id,
                    'at' => $activity->created_at,
                    'from' => TicketStatus::tryFrom((string) ($changes?->get('old')['status'] ?? '')),
                    'to' => $to,
                ];
            })
            ->filter();
    }
}
