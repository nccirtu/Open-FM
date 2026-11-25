<?php

namespace App\Policies;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ApiTokenPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_api::token');
    }

    public function view(User $user, ApiToken $apiToken): bool
    {
        return $user->can('view_api::token');
    }

    public function create(User $user): bool
    {
        return $user->can('create_api::token');
    }

    public function update(User $user, ApiToken $apiToken): bool
    {
        return $user->can('update_api::token');
    }

    public function delete(User $user, ApiToken $apiToken): bool
    {
        return $user->can('delete_api::token');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_api::token');
    }

    public function forceDelete(User $user, ApiToken $apiToken): bool
    {
        return $user->can('{{ ForceDelete }}');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('{{ ForceDeleteAny }}');
    }

    public function restore(User $user, ApiToken $apiToken): bool
    {
        return $user->can('{{ Restore }}');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('{{ RestoreAny }}');
    }

    public function replicate(User $user, ApiToken $apiToken): bool
    {
        return $user->can('{{ Replicate }}');
    }

    public function reorder(User $user): bool
    {
        return $user->can('{{ Reorder }}');
    }
}
