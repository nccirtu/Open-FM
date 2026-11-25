<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WasteCollection;
use Illuminate\Auth\Access\HandlesAuthorization;

class WasteCollectionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_waste::collection');
    }

    public function view(User $user, WasteCollection $wasteCollection): bool
    {
        return $user->can('view_waste::collection');
    }

    public function create(User $user): bool
    {
        return $user->can('create_waste::collection');
    }

    public function update(User $user, WasteCollection $wasteCollection): bool
    {
        return $user->can('update_waste::collection');
    }

    public function delete(User $user, WasteCollection $wasteCollection): bool
    {
        return $user->can('delete_waste::collection');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_waste::collection');
    }
}
