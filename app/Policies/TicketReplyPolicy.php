<?php

namespace App\Policies;

use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TicketReplyPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_ticket');
    }

    public function view(User $user, TicketReply $reply): bool
    {
        return $user->can('view_ticket');
    }

    public function create(User $user): bool
    {
        return $user->can('view_ticket');
    }

    public function update(User $user, TicketReply $reply): bool
    {
        return false;
    }

    public function delete(User $user, TicketReply $reply): bool
    {
        return false;
    }
}
