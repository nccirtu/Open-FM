<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WasteTreatment;
use Illuminate\Auth\Access\HandlesAuthorization;

class WasteTreatmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_waste::treatment');
    }

    public function view(User $user, WasteTreatment $wasteTreatment): bool
    {
        return $user->can('view_waste::treatment');
    }

    public function create(User $user): bool
    {
        return $user->can('create_waste::treatment');
    }

    public function update(User $user, WasteTreatment $wasteTreatment): bool
    {
        return $user->can('update_waste::treatment');
    }

    public function delete(User $user, WasteTreatment $wasteTreatment): bool
    {
        return $user->can('delete_waste::treatment');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_waste::treatment');
    }

    public function reorder(User $user): bool
    {
        return $user->can('update_waste::treatment');
    }
}
