<?php

namespace App\Policies;

use App\Models\AssetType;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AssetTypePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_asset');
    }

    public function view(User $user, AssetType $assetType): bool
    {
        return $user->can('view_any_asset');
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, AssetType $assetType): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, AssetType $assetType): bool
    {
        return $user->isSuperAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }
}
