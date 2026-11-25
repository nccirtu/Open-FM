<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrganizationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->administersAnyOrganization();
    }

    public function view(User $user, Organization $organization): bool
    {
        return $user->administersOrganization($organization);
    }

    public function create(User $user): bool
    {
        return $user->hasRoleInAnyOrganization('Admin');
    }

    public function update(User $user, Organization $organization): bool
    {
        return $user->administersOrganization($organization);
    }

    public function delete(User $user, Organization $organization): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
