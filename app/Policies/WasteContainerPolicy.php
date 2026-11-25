<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WasteContainer;
use Illuminate\Auth\Access\HandlesAuthorization;

class WasteContainerPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_waste::container');
    }

    public function view(User $user, WasteContainer $wasteContainer): bool
    {
        return $user->can('view_waste::container');
    }

    public function create(User $user): bool
    {
        return $user->can('create_waste::container');
    }

    public function update(User $user, WasteContainer $wasteContainer): bool
    {
        return $user->can('update_waste::container');
    }

    public function delete(User $user, WasteContainer $wasteContainer): bool
    {
        return $user->can('delete_waste::container');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_waste::container');
    }
}
