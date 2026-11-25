<?php

namespace App\Policies;

use App\Models\SlaDefinition;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SlaDefinitionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->administersAnyOrganization();
    }

    public function view(User $user, SlaDefinition $definition): bool
    {
        return $user->administersOrganization($definition->organization_id);
    }

    public function create(User $user): bool
    {
        return $user->administersAnyOrganization();
    }

    public function update(User $user, SlaDefinition $definition): bool
    {
        return $user->administersOrganization($definition->organization_id);
    }

    public function delete(User $user, SlaDefinition $definition): bool
    {
        return $user->administersOrganization($definition->organization_id);
    }

    public function deleteAny(User $user): bool
    {
        return $user->administersAnyOrganization();
    }
}
