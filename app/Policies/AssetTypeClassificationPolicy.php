<?php

namespace App\Policies;

use App\Models\AssetTypeClassification;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AssetTypeClassificationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_asset');
    }

    public function view(User $user, AssetTypeClassification $assetTypeClassification): bool
    {
        return $user->can('view_any_asset');
    }

    public function create(User $user): bool
    {
        return $user->can('update_asset');
    }

    public function update(User $user, AssetTypeClassification $assetTypeClassification): bool
    {
        return $user->can('update_asset');
    }

    public function delete(User $user, AssetTypeClassification $assetTypeClassification): bool
    {
        return $user->can('update_asset');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('update_asset');
    }
}
