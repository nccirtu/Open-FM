<?php

namespace App\Policies;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AssetPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_asset');
    }

    public function view(User $user, Asset $asset): bool
    {
        return $user->can('view_asset');
    }

    public function create(User $user): bool
    {
        return $user->can('create_asset');
    }

    public function update(User $user, Asset $asset): bool
    {
        return $user->can('update_asset');
    }

    public function delete(User $user, Asset $asset): bool
    {
        return $user->can('delete_asset');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_asset');
    }

    public function forceDelete(User $user, Asset $asset): bool
    {
        return $user->can('{{ ForceDelete }}');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('{{ ForceDeleteAny }}');
    }

    public function restore(User $user, Asset $asset): bool
    {
        return $user->can('{{ Restore }}');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('{{ RestoreAny }}');
    }

    public function replicate(User $user, Asset $asset): bool
    {
        return $user->can('{{ Replicate }}');
    }

    public function reorder(User $user): bool
    {
        return $user->can('{{ Reorder }}');
    }
}
