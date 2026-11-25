<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use App\Models\Ticket;
use App\Support\ServicePartner;
use Illuminate\Database\Eloquent\Builder;

trait RestrictsTickets
{
    /**
     * @return Builder<Ticket>
     */
    protected function visibleTickets(): Builder
    {
        $user = $this->apiUser();
        $tickets = ServicePartner::restrict(Ticket::query(), $user);

        if ($this->isReporterOnly()) {
            $tickets->visibleInPortal($user);
        }

        return $tickets;
    }

    protected function authorizeTicket(string $ability, Ticket $ticket): void
    {
        $this->authorizeApi($ability, $ticket);

        abort_unless($this->visibleTickets()->whereKey($ticket->getKey())->exists(), 403);
    }

    protected function isReporterOnly(): bool
    {
        $roles = $this->apiUser()->roles->pluck('name');

        return $roles->contains('Melder') && $roles->diff(['Melder'])->isEmpty();
    }
}
