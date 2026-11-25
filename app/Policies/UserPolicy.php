<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_user');
    }

    public function view(User $user): bool
    {
        return $user->can('view_user');
    }

    public function create(User $user): bool
    {
        return $user->can('create_user');
    }

    public function update(User $user): bool
    {
        return $user->can('update_user');
    }

    public function delete(User $user): bool
    {
        return $user->can('delete_user');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_user');
    }

    public function forceDelete(User $user): bool
    {
        return $user->can('{{ ForceDelete }}');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('{{ ForceDeleteAny }}');
    }

    public function restore(User $user): bool
    {
        return $user->can('{{ Restore }}');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('{{ RestoreAny }}');
    }

    public function replicate(User $user): bool
    {
        return $user->can('{{ Replicate }}');
    }

    public function reorder(User $user): bool
    {
        return $user->can('{{ Reorder }}');
    }
}
