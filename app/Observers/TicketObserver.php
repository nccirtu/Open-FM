<?php

namespace App\Observers;

use App\Enums\TicketStatus;
use App\Models\SlaDefinition;
use App\Models\Ticket;
use App\Models\TicketAutomationRule;
use App\Services\TicketAutomationService;
use Filament\Notifications\Notification;

class TicketObserver
{
    public function creating(Ticket $ticket): void
    {
        $ticket->reported_by ??= auth()->id();
        $ticket->last_activity_at = now();

        $this->touchStatusTimestamps($ticket);
        $this->applySla($ticket);
    }

    public function updating(Ticket $ticket): void
    {
        if ($ticket->isDirty(['status', 'assigned_to', 'priority'])) {
            $ticket->last_activity_at = now();
        }

        $this->touchStatusTimestamps($ticket);

        if ($ticket->isDirty('priority')) {
            $this->applySla($ticket);
        }

        if ($ticket->isDirty('status')) {
            $this->handleSuspension($ticket);
        }
    }

    public function created(Ticket $ticket): void
    {
        $this->notifyAssignee($ticket);

        app(TicketAutomationService::class)->run($ticket, TicketAutomationRule::EVENT_CREATED);
    }

    public function updated(Ticket $ticket): void
    {
        if ($ticket->wasChanged('status')) {
            $this->notifyReporterAboutStatus($ticket);

            app(TicketAutomationService::class)->run($ticket, TicketAutomationRule::EVENT_STATUS_CHANGED);
        }

        if ($ticket->wasChanged('assigned_to')) {
            $this->notifyAssignee($ticket);

            app(TicketAutomationService::class)->run($ticket, TicketAutomationRule::EVENT_ASSIGNED);
        }
    }

    private function applySla(Ticket $ticket): void
    {
        $definition = SlaDefinition::forTicket($ticket);

        if ($definition === null) {
            return;
        }

        $start = $ticket->created_at ?? now();

        $ticket->respond_due_at = $start->copy()->addHours($definition->response_hours);
        $ticket->resolve_due_at = $start->copy()->addHours($definition->resolution_hours);
    }

    private function handleSuspension(Ticket $ticket): void
    {
        if ($ticket->status === TicketStatus::Waiting) {
            $ticket->suspended_at = now();

            return;
        }

        if ($ticket->getOriginal('status') === TicketStatus::Waiting && $ticket->suspended_at !== null) {
            $pausedSeconds = (int) $ticket->suspended_at->diffInSeconds(now());

            $ticket->respond_due_at = $ticket->respond_due_at?->addSeconds($pausedSeconds);
            $ticket->resolve_due_at = $ticket->resolve_due_at?->addSeconds($pausedSeconds);
            $ticket->suspended_at = null;
            $ticket->suspension_reason = null;
        }
    }

    private function touchStatusTimestamps(Ticket $ticket): void
    {
        if ($ticket->isDirty('status') && $ticket->status === TicketStatus::Resolved && $ticket->resolved_at === null) {
            $ticket->resolved_at = now();
        }
    }

    private function notifyReporterAboutStatus(Ticket $ticket): void
    {
        $reporter = $ticket->reporter;

        if ($reporter === null || $reporter->getKey() === auth()->id()) {
            return;
        }

        Notification::make()
            ->title(__('tickets.notifications.status.title'))
            ->body(__('tickets.notifications.status.body', [
                'title' => $ticket->title,
                'status' => $ticket->status->getLabel(),
            ]))
            ->info()
            ->sendToDatabase($reporter);
    }

    private function notifyAssignee(Ticket $ticket): void
    {
        $assignee = $ticket->assignee;

        if ($assignee === null || $assignee->getKey() === auth()->id()) {
            return;
        }

        Notification::make()
            ->title(__('tickets.notifications.assigned.title'))
            ->body(__('tickets.notifications.assigned.body', [
                'title' => $ticket->title,
                'property' => trim(($ticket->property?->property_number ?? '').' '.($ticket->property?->city ?? '')),
            ]))
            ->warning()
            ->sendToDatabase($assignee);
    }
}
