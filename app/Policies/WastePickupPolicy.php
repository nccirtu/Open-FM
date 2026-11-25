<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WastePickup;
use Illuminate\Auth\Access\HandlesAuthorization;

class WastePickupPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_waste::pickup');
    }

    public function view(User $user, WastePickup $wastePickup): bool
    {
        return $user->can('view_waste::pickup');
    }

    public function create(User $user): bool
    {
        return $user->can('create_waste::pickup');
    }

    public function update(User $user, WastePickup $wastePickup): bool
    {
        return $user->can('update_waste::pickup');
    }

    public function delete(User $user, WastePickup $wastePickup): bool
    {
        return $user->can('delete_waste::pickup');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_waste::pickup');
    }

    public function confirm(User $user, ?WastePickup $wastePickup = null): bool
    {
        return $user->can('confirm_waste::pickup');
    }
}
