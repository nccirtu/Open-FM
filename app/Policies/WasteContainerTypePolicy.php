<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WasteContainerType;
use Illuminate\Auth\Access\HandlesAuthorization;

class WasteContainerTypePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_waste::container::type');
    }

    public function view(User $user, WasteContainerType $wasteContainerType): bool
    {
        return $user->can('view_waste::container::type');
    }

    public function create(User $user): bool
    {
        return $user->can('create_waste::container::type');
    }

    public function update(User $user, WasteContainerType $wasteContainerType): bool
    {
        return $user->can('update_waste::container::type');
    }

    public function delete(User $user, WasteContainerType $wasteContainerType): bool
    {
        return $user->can('delete_waste::container::type');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_waste::container::type');
    }

    public function reorder(User $user): bool
    {
        return $user->can('update_waste::container::type');
    }
}
