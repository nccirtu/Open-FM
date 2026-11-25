<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WasteFractionGroup;
use Illuminate\Auth\Access\HandlesAuthorization;

class WasteFractionGroupPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_waste::fraction::group');
    }

    public function view(User $user, WasteFractionGroup $wasteFractionGroup): bool
    {
        return $user->can('view_waste::fraction::group');
    }

    public function create(User $user): bool
    {
        return $user->can('create_waste::fraction::group');
    }

    public function update(User $user, WasteFractionGroup $wasteFractionGroup): bool
    {
        return $user->can('update_waste::fraction::group');
    }

    public function delete(User $user, WasteFractionGroup $wasteFractionGroup): bool
    {
        return $user->can('delete_waste::fraction::group');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_waste::fraction::group');
    }

    public function reorder(User $user): bool
    {
        return $user->can('update_waste::fraction::group');
    }
}
