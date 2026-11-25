<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TicketPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_ticket');
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $user->can('view_ticket');
    }

    public function create(User $user): bool
    {
        return $user->can('create_ticket');
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $user->can('update_ticket');
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->can('delete_ticket');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_ticket');
    }
}
