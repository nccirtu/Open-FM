<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WasteGoal;
use Illuminate\Auth\Access\HandlesAuthorization;

class WasteGoalPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_waste::goal');
    }

    public function view(User $user, WasteGoal $wasteGoal): bool
    {
        return $user->can('view_waste::goal');
    }

    public function create(User $user): bool
    {
        return $user->can('create_waste::goal');
    }

    public function update(User $user, WasteGoal $wasteGoal): bool
    {
        return $user->can('update_waste::goal');
    }

    public function delete(User $user, WasteGoal $wasteGoal): bool
    {
        return $user->can('delete_waste::goal');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_waste::goal');
    }
}
