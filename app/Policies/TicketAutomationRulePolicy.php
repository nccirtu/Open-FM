<?php

namespace App\Policies;

use App\Models\TicketAutomationRule;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TicketAutomationRulePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->administersAnyOrganization();
    }

    public function view(User $user, TicketAutomationRule $rule): bool
    {
        return $user->administersOrganization($rule->organization_id);
    }

    public function create(User $user): bool
    {
        return $user->administersAnyOrganization();
    }

    public function update(User $user, TicketAutomationRule $rule): bool
    {
        return $user->administersOrganization($rule->organization_id);
    }

    public function delete(User $user, TicketAutomationRule $rule): bool
    {
        return $user->administersOrganization($rule->organization_id);
    }

    public function deleteAny(User $user): bool
    {
        return $user->administersAnyOrganization();
    }
}
