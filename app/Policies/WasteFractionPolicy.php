<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WasteFraction;
use Illuminate\Auth\Access\HandlesAuthorization;

class WasteFractionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_waste::fraction');
    }

    public function view(User $user, WasteFraction $wasteFraction): bool
    {
        return $user->can('view_waste::fraction');
    }

    public function create(User $user): bool
    {
        return $user->can('create_waste::fraction');
    }

    public function update(User $user, WasteFraction $wasteFraction): bool
    {
        return $user->can('update_waste::fraction');
    }

    public function delete(User $user, WasteFraction $wasteFraction): bool
    {
        return $user->can('delete_waste::fraction');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_waste::fraction');
    }

    public function reorder(User $user): bool
    {
        return $user->can('update_waste::fraction');
    }
}
