<?php

namespace App\Console\Commands;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CheckTicketSla extends Command
{
    protected $signature = 'tickets:check-sla';

    protected $description = 'Eskaliert Tickets mit verletzter SLA (Reaktions- oder Lösungsfrist überschritten)';

    public function handle(): int
    {
        $escalated = 0;

        Ticket::query()
            ->whereIn('status', TicketStatus::open())
            ->whereNull('sla_escalated_at')
            ->where(fn ($query) => $query->whereNotNull('respond_due_at')->orWhereNotNull('resolve_due_at'))
            ->get()
            ->filter(fn (Ticket $ticket): bool => $ticket->isSlaBreached())
            ->each(function (Ticket $ticket) use (&$escalated): void {
                foreach ($this->recipientsFor($ticket) as $recipient) {
                    Notification::make()
                        ->title(__('tickets.sla.escalation.title'))
                        ->body(__('tickets.sla.escalation.body', [
                            'title' => $ticket->title,
                            'due' => $ticket->resolve_due_at?->format('d.m.Y H:i') ?? '—',
                        ]))
                        ->danger()
                        ->sendToDatabase($recipient);
                }

                $ticket->forceFill(['sla_escalated_at' => now()])->saveQuietly();
                $escalated++;
            });

        $this->info("{$escalated} Tickets eskaliert.");

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, User>
     */
    private function recipientsFor(Ticket $ticket): Collection
    {
        $modelHasRolesTable = config('permission.table_names.model_has_roles');
        $rolesTable = config('permission.table_names.roles');

        $adminIds = DB::table($modelHasRolesTable)
            ->join($rolesTable, "{$rolesTable}.id", '=', "{$modelHasRolesTable}.role_id")
            ->where("{$rolesTable}.organization_id", $ticket->organization_id)
            ->where("{$rolesTable}.name", 'Admin')
            ->where("{$modelHasRolesTable}.model_type", (new User)->getMorphClass())
            ->pluck("{$modelHasRolesTable}.model_id");

        return User::query()
            ->whereIn('id', $adminIds->when($ticket->assigned_to, fn ($ids) => $ids->push($ticket->assigned_to)))
            ->get();
    }
}
