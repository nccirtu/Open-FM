<?php

namespace App\Observers;

use App\Models\TicketAutomationRule;
use App\Models\TicketReply;
use App\Models\User;
use App\Services\TicketAutomationService;
use Filament\Notifications\Notification;

class TicketReplyObserver
{
    public function creating(TicketReply $reply): void
    {
        $reply->user_id ??= auth()->id();
        $reply->organization_id ??= $reply->ticket?->organization_id;
    }

    public function created(TicketReply $reply): void
    {
        $ticket = $reply->ticket;

        if ($ticket === null) {
            return;
        }

        $isAgentReply = $reply->user_id !== null && $reply->user_id !== $ticket->reported_by;

        $ticket->forceFill([
            'last_activity_at' => now(),
            'first_response_at' => $ticket->first_response_at ?? ($isAgentReply ? now() : null),
        ])->saveQuietly();

        $recipients = collect([$ticket->assignee])
            ->when(! $reply->is_internal_note, fn ($collection) => $collection->push($ticket->reporter))
            ->filter()
            ->unique(fn (User $user): string => $user->getKey())
            ->reject(fn (User $user): bool => $user->getKey() === $reply->user_id);

        foreach ($recipients as $recipient) {
            Notification::make()
                ->title(__('tickets.notifications.reply.title'))
                ->body(__('tickets.notifications.reply.body', [
                    'title' => $ticket->title,
                    'author' => $reply->author?->name ?? '—',
                ]))
                ->info()
                ->sendToDatabase($recipient);
        }

        app(TicketAutomationService::class)->run($ticket, TicketAutomationRule::EVENT_REPLY_ADDED);
    }
}
