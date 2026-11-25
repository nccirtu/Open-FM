<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WasteEmissionFactor;
use Illuminate\Auth\Access\HandlesAuthorization;

class WasteEmissionFactorPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_waste::emission::factor');
    }

    public function view(User $user, WasteEmissionFactor $wasteEmissionFactor): bool
    {
        return $user->can('view_waste::emission::factor');
    }

    public function create(User $user): bool
    {
        return $user->can('create_waste::emission::factor');
    }

    public function update(User $user, WasteEmissionFactor $wasteEmissionFactor): bool
    {
        return $user->can('update_waste::emission::factor');
    }

    public function delete(User $user, WasteEmissionFactor $wasteEmissionFactor): bool
    {
        return $user->can('delete_waste::emission::factor');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_waste::emission::factor');
    }
}
