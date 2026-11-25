<?php

namespace App\Services;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketAutomationRule;
use App\Models\TicketReply;

class TicketAutomationService
{
    private static bool $running = false;

    public function run(Ticket $ticket, string $event): void
    {
        if (self::$running) {
            return;
        }

        $rules = TicketAutomationRule::query()
            ->where('is_active', true)
            ->where('event', $event)
            ->where('organization_id', $ticket->organization_id)
            ->orderBy('id')
            ->get();

        if ($rules->isEmpty()) {
            return;
        }

        self::$running = true;

        try {
            foreach ($rules as $rule) {
                if (! $this->matches($rule, $ticket)) {
                    continue;
                }

                $this->applyActions($rule, $ticket);
            }
        } finally {
            self::$running = false;
        }
    }

    private function matches(TicketAutomationRule $rule, Ticket $ticket): bool
    {
        foreach ($rule->conditions ?? [] as $condition) {
            $field = $condition['field'] ?? null;
            $value = $condition['value'] ?? null;

            if (! in_array($field, TicketAutomationRule::CONDITION_FIELDS, true)) {
                continue;
            }

            $actual = $ticket->getAttribute($field);
            $actual = $actual instanceof \BackedEnum ? $actual->value : $actual;

            if ((string) $actual !== (string) $value) {
                return false;
            }
        }

        return true;
    }

    private function applyActions(TicketAutomationRule $rule, Ticket $ticket): void
    {
        $applied = [];

        foreach ($rule->actions ?? [] as $action) {
            $type = $action['type'] ?? null;
            $value = $action['value'] ?? null;

            match ($type) {
                'assign_to' => $ticket->assigned_to = (string) $value,
                'set_status' => $ticket->status = TicketStatus::from($value),
                'set_priority' => $ticket->priority = TicketPriority::from($value),
                'add_internal_note' => $this->addInternalNote($ticket, (string) ($action['note'] ?? $value)),
                default => null,
            };

            if ($type !== null) {
                $applied[] = $type;
            }
        }

        if ($ticket->isDirty()) {
            if ($ticket->isDirty('status') && $ticket->status === TicketStatus::Resolved && $ticket->resolved_at === null) {
                $ticket->resolved_at = now();
            }

            $ticket->saveQuietly();
        }

        if ($applied !== []) {
            activity()
                ->performedOn($ticket)
                ->event('automation')
                ->withProperties(['rule' => $rule->name, 'actions' => $applied])
                ->log('automation');
        }
    }

    private function addInternalNote(Ticket $ticket, string $message): void
    {
        $note = new TicketReply([
            'message' => $message,
            'is_internal_note' => true,
        ]);

        $note->ticket_id = $ticket->getKey();
        $note->organization_id = $ticket->organization_id;
        $note->saveQuietly();
    }
}
